<?php

declare(strict_types=1);

namespace App\Filament\Support\Exports;

use App\Models\User;
use App\Services\Export\ExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Throwable;

/**
 * ExportRecordsAction — the shared "Export" header action for every
 * List page that has an exportable surface (#225 master data, #226
 * transactions and stock ledger).
 *
 * One subclass per List page declares its action name and the registry
 * keys it offers; everything else — the type and format choice, the
 * server-side authorization gate, and running the export against the
 * table's own scoped and filtered query — comes from the shared base.
 *
 * The export always runs against `getTableQueryForExport()`, the same
 * query the user is looking at: warehouse scope, table filters, search
 * and sort are applied by Filament before the snapshot is taken, so the
 * file can never contain a warehouse the user cannot see or rows the
 * table would not show.
 */
abstract class ExportRecordsAction
{
    /** Unique action name on the page, e.g. "exportWarehouses". */
    abstract protected function name(): string;

    /**
     * Registry keys (config/exports.php) this action can export, in
     * display order. More than one key shows the type selector.
     *
     * @return array<int, string>
     */
    abstract protected function definitionKeys(): array;

    public static function make(): Action
    {
        $instance = new static;

        return Action::make($instance->name())
            ->label(__('exports.action.label'))
            ->modalHeading(__('exports.action.heading'))
            ->modalDescription(__('exports.action.description'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('primary')
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel(__('exports.action.submit_label'))
            ->schema(fn (): array => $instance->schema())
            ->authorize(static function () use ($instance): bool {
                $user = auth()->user();

                if (! $user instanceof User) {
                    return false;
                }

                $service = app(ExportService::class);

                foreach ($instance->definitionKeys() as $key) {
                    try {
                        if ($service->resolveDefinition($key)->can($user)) {
                            return true;
                        }
                    } catch (Throwable) {
                        continue;
                    }
                }

                return false;
            })
            ->action(static fn (array $data, Component $livewire) => $instance->runExport($data, $livewire));
    }

    /**
     * Type and format selectors. The type selector only appears when
     * the page offers more than one exportable type.
     *
     * @return array<int, mixed>
     */
    protected function schema(): array
    {
        $keys = $this->definitionKeys();

        $schema = [];

        if (count($keys) > 1) {
            $schema[] = ToggleButtons::make('type')
                ->label(__('exports.action.type_label'))
                ->options(collect($keys)
                    ->mapWithKeys(fn (string $key): array => [$key => $this->typeLabel($key)])
                    ->all())
                ->default($keys[0])
                ->columnSpanFull()
                ->live()
                ->required();
        }

        $schema[] = ToggleButtons::make('format')
            ->label(__('exports.action.format_label'))
            ->options([
                'csv' => 'CSV',
                'xlsx' => 'XLSX',
            ])
            ->icons([
                'csv' => Heroicon::DocumentText,
                'xlsx' => Heroicon::TableCells,
            ])
            ->default('csv')
            ->columnSpanFull()
            ->required();

        return $schema;
    }

    /**
     * Type label, resolved through the definition so the registry stays
     * the single source of truth for an export's display name.
     */
    protected function typeLabel(string $key): string
    {
        return __(app(ExportService::class)->resolveDefinition($key)->labelKey());
    }

    /**
     * Run the export against the page's table query and report the
     * result as a notification with its download link.
     *
     * The three guards below are unreachable by construction — the action
     * is only ever registered as a header action on an authenticated List
     * page, so the actor is a user, the component is a `HasTable`, and
     * Filament always hands back a builder. They stay silent because there
     * is no user left to notify.
     *
     * @param  array<string, mixed>  $data
     */
    protected function runExport(array $data, Component $livewire): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        if (! $livewire instanceof HasTable) {
            return;
        }

        $tableQuery = $livewire->getTableQueryForExport();

        if (! $tableQuery instanceof Builder) {
            return;
        }

        $keys = $this->definitionKeys();
        $key = (string) ($data['type'] ?? $keys[0]);
        $format = strtolower((string) ($data['format'] ?? 'csv'));

        if (! in_array($key, $keys, true)) {
            Notification::make()
                ->danger()
                ->title(__('exports.errors.unknown_definition', ['key' => $key]))
                ->send();

            return;
        }

        try {
            $result = app(ExportService::class)->export($tableQuery, $key, $format, $user);
        } catch (Throwable $exception) {
            // Mirrors ProcessExportJob: any engine failure degrades to the
            // failure notification rather than an exception page, so the
            // user is told the export did not happen.
            report($exception);

            Notification::make()
                ->danger()
                ->title(__('exports.notifications.failed_title'))
                ->body(__('exports.notifications.failed_body'))
                ->send();

            return;
        }

        $body = $result['status'] === 'queued'
            ? __('exports.notifications.queued_body', [
                'rows' => number_format($result['rows']),
                'type' => $this->typeLabel($key),
            ])
            : __('exports.notifications.completed_body', [
                'rows' => number_format($result['rows']),
                'type' => $this->typeLabel($key),
            ]);

        $notification = Notification::make()
            ->success()
            ->title($result['status'] === 'queued'
                ? __('exports.notifications.queued_title')
                : __('exports.notifications.completed_title'))
            ->body($body);

        if ($result['status'] === 'completed') {
            $notification->actions([
                Action::make('download')
                    ->label(__('exports.notifications.download_label'))
                    // Filament's SPA mode (->spa()) marks every in-panel
                    // link `wire:navigate`, which fetches the URL with
                    // fetch() and swaps the CSV text into the page instead
                    // of downloading it. A new tab skips that interception
                    // — the same escape hatch ImportWizardAction already
                    // uses for its template downloads.
                    ->url(route('exports.download', ['file' => $result['filename']]), shouldOpenInNewTab: true),
            ]);
        }

        $notification->send();
    }
}
