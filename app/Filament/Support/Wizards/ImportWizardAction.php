<?php

declare(strict_types=1);

namespace App\Filament\Support\Wizards;

use App\Exceptions\DomainRuleViolationException;
use App\Models\User;
use App\Services\Import\ImportDefinition;
use App\Services\Import\ImportFileStore;
use App\Services\Import\ImportParser;
use App\Services\Import\ImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * ImportWizardAction — the shared multi-step import wizard used by the
 * whole import/export suite: upload + template download (+ optional
 * import-type selector), header mapping with suggestions, and a frozen
 * preview with explicit confirmation.
 *
 * The preview is computed once when the mapping step is completed and
 * frozen server-side next to the uploaded file, so the counts the user
 * confirms are exactly the counts the execution compares against. On a
 * database drift the engine throws `errors.import_counts_mismatch`;
 * this action then refreshes the frozen preview, clears the
 * confirmation checkbox and surfaces the difference inline — nothing is
 * written, and a brand-new confirmation is required.
 *
 * Feature actions extend this class and only declare their Livewire
 * action name, their definition registry keys and their translation
 * key prefix.
 */
abstract class ImportWizardAction
{
    /** Livewire action name, e.g. "importProducts". */
    abstract protected function name(): string;

    /**
     * Registry keys this action may import. The first is the default;
     * a single key renders no mode selector, more than one renders one.
     *
     * @return array<int, string>
     */
    abstract protected function definitionKeys(): array;

    /**
     * Translation key prefix shared by label / heading / description,
     * e.g. "resources.products.actions.import".
     */
    abstract protected function translationKeyPrefix(): string;

    public static function make(): Action
    {
        $instance = new static;

        return Action::make($instance->name())
            ->label(fn (): string => __($instance->translationKeyPrefix()))
            ->modalHeading(fn (): string => __($instance->translationKeyPrefix().'_heading'))
            ->modalDescription(fn (): string => __($instance->translationKeyPrefix().'_description'))
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('primary')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitActionLabel(__('imports.wizard.submit_label'))
            ->modalCancelAction(fn (Action $action) => $action->action(
                // Cancelling drops only stale leftovers: a queued import's
                // source file shares this directory, and the upload just
                // staged here is swept too once it reaches the stale horizon.
                static fn () => app(ImportFileStore::class)->sweep((int) auth()->id()),
            ))
            ->steps([
                $instance->uploadStep(),
                $instance->mappingStep(),
                $instance->previewStep(),
            ])
            ->authorize(static function () use ($instance): bool {
                $user = auth()->user();

                if (! $user instanceof User) {
                    return false;
                }

                $service = app(ImportService::class);

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
            ->action(static fn (array $data, Schema $schema) => $instance->runImport($data, $schema));
    }

    protected function uploadStep(): Step
    {
        return Step::make(__('imports.wizard.step_upload'))
            ->description(__('imports.wizard.step_upload_desc'))
            ->icon(Heroicon::ArrowUpTray)
            ->schema(fn (): array => $this->uploadSchema());
    }

    protected function mappingStep(): Step
    {
        return Step::make(__('imports.wizard.step_mapping'))
            ->description(__('imports.wizard.step_mapping_desc'))
            ->icon(Heroicon::ArrowsRightLeft)
            ->schema(function (Get $get): array {
                $headers = $this->headersFromFile($get('file'));
                $headerOptions = $headers !== [] ? array_combine($headers, $headers) : [];

                if ($headerOptions === []) {
                    return [
                        View::make('filament.wizards.mapping-notice')
                            ->columnSpanFull(),
                    ];
                }

                $definition = $this->currentDefinition($get('mode'));
                $guesses = app(ImportService::class)->guessMappings($definition, $headers);

                $selects = [];

                foreach ($definition->columns() as $column) {
                    $selects[] = Select::make('columnMap.'.$column->key)
                        ->label($column->label().($column->required ? ' *' : ''))
                        ->helperText($column->description())
                        ->options($headerOptions)
                        ->default(fn () => $guesses[$column->key] ?? null)
                        ->searchable()
                        ->required($column->required);
                }

                return [
                    Fieldset::make(__('imports.wizard.mapping_fieldset'))
                        ->columns(2)
                        ->schema($selects)
                        ->columnSpanFull(),
                ];
            })
            ->afterValidation(fn (Get $get) => $this->freezePreview($get));
    }

    protected function previewStep(): Step
    {
        return Step::make(__('imports.wizard.step_preview'))
            ->description(__('imports.wizard.step_preview_desc'))
            ->icon(Heroicon::Eye)
            ->schema(fn (): array => [
                View::make('filament.wizards.import-preview')
                    ->viewData(fn (): array => ['preview' => $this->frozenPreview()])
                    ->columnSpanFull(),

                Checkbox::make('confirm_updates')
                    ->label(__('imports.wizard.confirm_updates'))
                    ->helperText(__('imports.wizard.confirm_updates_help'))
                    ->visible(fn (): bool => (bool) ($this->frozenPreview()['has_updates'] ?? false))
                    ->required(fn (): bool => (bool) ($this->frozenPreview()['has_updates'] ?? false)),
            ]);
    }

    /** @return array<int, mixed> */
    protected function uploadSchema(): array
    {
        $definitionKeys = $this->definitionKeys();

        $schema = [];
        $templateButtons = [];

        if (count($definitionKeys) > 1) {
            $service = app(ImportService::class);

            $schema[] = ToggleButtons::make('mode')
                ->label(__('imports.wizard.mode_label'))
                ->options(static fn (): array => collect($definitionKeys)
                    ->mapWithKeys(static fn (string $key): array => [$key => $service->resolveDefinition($key)->label()])
                    ->all())
                ->icons(static fn (): array => collect($definitionKeys)
                    ->mapWithKeys(static fn (string $key): array => [$key => static::modeIcon($key)])
                    ->all())
                ->tooltips(static fn (): array => collect($definitionKeys)
                    ->mapWithKeys(static fn (string $key): array => [$key => $service->resolveDefinition($key)->description()])
                    ->all())
                ->helperText(fn (Get $get): string => $this->currentDefinition($get('mode'))->description())
                ->default($definitionKeys[0])
                ->columnSpanFull()
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set): void {
                    $this->applyMappingGuesses($get, $set);
                })
                ->required();
        }

        $formatIcons = [
            'csv' => Heroicon::DocumentText,
            'xlsx' => Heroicon::TableCells,
        ];

        foreach (['csv' => 'imports.wizard.download_csv_template', 'xlsx' => 'imports.wizard.download_xlsx_template'] as $format => $labelKey) {
            $templateButtons[] = Action::make('download_'.$format.'_template')
                ->label(__($labelKey))
                ->icon($formatIcons[$format])
                ->color('gray')
                ->url(fn (Get $get) => route('imports.template', [
                    'import' => $this->currentDefinitionKey($get('mode')),
                    'format' => $format,
                ]), shouldOpenInNewTab: true);
        }

        $schema[] = Actions::make($templateButtons);

        $schema[] = FileUpload::make('file')
            ->label(__('imports.wizard.file_label'))
            ->helperText(__('imports.wizard.file_helper'))
            ->acceptedFileTypes([
                'text/csv',
                'text/x-csv',
                'application/csv',
                'text/x-csv',
                'text/comma-separated-values',
                'text/x-comma-separated-values',
                'text/plain',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->disk(ImportFileStore::DISK)
            ->directory(static fn (): string => ImportFileStore::DIRECTORY.'/'.auth()->id())
            ->visibility('private')
            ->required()
            ->live()
            ->afterStateUpdated(function (Get $get, Set $set): void {
                $this->applyMappingGuesses($get, $set);
            })
            ->columnSpanFull();

        return $schema;
    }

    /**
     * Freeze the preview server-side when the mapping step completes:
     * build it from the current file + mapping + database, sweep every
     * other file in the user's import directory, then store it as
     * preview.json so step 3 and the execution share one source of
     * truth for the confirmed counts.
     */
    protected function freezePreview(Get $get): void
    {
        /** @var User $user */
        $user = auth()->user();

        $fileStore = app(ImportFileStore::class);
        $filePath = $fileStore->resolvePhysicalPath($get('file'));

        try {
            if (blank($filePath)) {
                throw new RuntimeException(__('imports.errors.file_missing'));
            }

            $preview = app(ImportService::class)->preview(
                $filePath,
                $fileStore->resolveOriginalName($get('file')),
                $this->currentDefinitionKey($get('mode')),
                (array) ($get('columnMap') ?? []),
                $user,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $preview = [
                'rows' => [[
                    'row_number' => 0,
                    'status' => 'reject',
                    'errors' => [$exception->getMessage()],
                    'identifier' => '-',
                ]],
                'counts' => ['create' => 0, 'update' => 0, 'reject' => 1, 'total' => 1],
                'has_updates' => false,
            ];
        }

        $fileStore->sweep((int) $user->getKey(), [$filePath]);
        $fileStore->writePreview((int) $user->getKey(), $preview);
    }

    /** @return array<string, mixed> */
    protected function frozenPreview(): array
    {
        return app(ImportFileStore::class)->readPreview((int) auth()->id())
            ?? [
                'rows' => [],
                'counts' => ['create' => 0, 'update' => 0, 'reject' => 0, 'total' => 0],
                'has_updates' => false,
            ];
    }

    protected function runImport(array $data, Schema $schema): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $fileStore = app(ImportFileStore::class);
        $service = app(ImportService::class);
        $fileState = $data['file'] ?? null;
        $filePath = $fileStore->resolvePhysicalPath($fileState);

        if (blank($filePath)) {
            Notification::make()
                ->danger()
                ->title(__('imports.notifications.failed_title'))
                ->body(__('imports.errors.file_missing'))
                ->send();

            return;
        }

        $preview = $fileStore->readPreview((int) $user->getKey());

        if (! is_array($preview)) {
            throw ValidationException::withMessages([
                $schema->getStatePath().'.confirm_updates' => __('imports.errors.preview_expired'),
            ]);
        }

        $definitionKey = $this->currentDefinitionKey($data['mode'] ?? null);
        $columnMap = (array) ($data['columnMap'] ?? []);
        $originalName = $fileStore->resolveOriginalName($fileState);

        try {
            $result = $service->execute(
                $filePath,
                $originalName,
                $definitionKey,
                $columnMap,
                $preview['counts'],
                (bool) ($data['confirm_updates'] ?? false),
                $user,
            );
        } catch (DomainRuleViolationException $exception) {
            throw $this->handleDomainViolation($exception, $schema, $filePath, $originalName, $definitionKey, $columnMap, $user);
        }

        if ($result['status'] === 'queued') {
            Notification::make()
                ->success()
                ->title(__('imports.notifications.queued_title'))
                ->body(__('imports.notifications.queued_body', [
                    'count' => ($result['counts']['create'] ?? 0) + ($result['counts']['update'] ?? 0),
                ]))
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__('imports.notifications.completed_title'))
            ->body(__('imports.notifications.completed_body', [
                'created' => $result['counts']['created'] ?? 0,
                'updated' => $result['counts']['updated'] ?? 0,
                'rejected' => $result['counts']['rejected'] ?? 0,
                'skipped' => $result['counts']['skipped'] ?? 0,
            ]))
            ->send();

        $fileStore->cleanupUser((int) $user->getKey());
    }

    /**
     * Convert domain violations raised at confirmation time into inline
     * validation feedback. A counts mismatch refreshes the frozen
     * preview (showing the difference), clears the confirmation and
     * requires the user to review and confirm again.
     */
    protected function handleDomainViolation(
        DomainRuleViolationException $exception,
        Schema $schema,
        string $filePath,
        ?string $originalName,
        string $definitionKey,
        array $columnMap,
        User $user,
    ): never {
        $field = match ($exception->translationKey()) {
            'errors.import_counts_mismatch' => 'confirm_updates',
            'errors.import_update_confirmation_required' => 'confirm_updates',
            default => null,
        };

        if ($field === null) {
            throw $exception;
        }

        if ($exception->translationKey() === 'errors.import_counts_mismatch') {
            $fresh = app(ImportService::class)->preview(
                $filePath,
                $originalName,
                $definitionKey,
                $columnMap,
                $user,
            );

            app(ImportFileStore::class)->writePreview((int) $user->getKey(), $fresh);
        }

        $checkbox = $schema->getComponent(
            static fn (Component $component): bool => $component instanceof Field
                && $component->getName() === 'confirm_updates',
            withActions: false,
            withHidden: true,
        );

        if ($checkbox instanceof Field) {
            $checkbox->makeSetUtility()($checkbox, null);
        }

        throw ValidationException::withMessages([
            $schema->getStatePath().'.'.$field => __($exception->translationKey().'.body', $exception->context()),
        ]);
    }

    protected function currentDefinition(?string $mode): ImportDefinition
    {
        return app(ImportService::class)->resolveDefinition(
            $this->currentDefinitionKey($mode),
        );
    }

    protected function currentDefinitionKey(?string $mode): string
    {
        $keys = $this->definitionKeys();

        if (count($keys) === 1) {
            return $keys[0];
        }

        return in_array($mode, $keys, true) ? $mode : $keys[0];
    }

    /**
     * Presentation-only icon per definition key for the upload-step
     * mode selector; unknown keys fall back to the stack icon.
     */
    protected static function modeIcon(string $key): Heroicon
    {
        return match ($key) {
            'products.variants' => Heroicon::Swatch,
            'warehouses' => Heroicon::BuildingStorefront,
            'suppliers' => Heroicon::Truck,
            'customers' => Heroicon::UserGroup,
            default => Heroicon::RectangleStack,
        };
    }

    /**
     * Seed the mapping selects from guessMappings() whenever the file
     * or the import mode changes.
     *
     * Wizard steps render lazily, so the mapping selects do not exist
     * when the action mounts and their ->default() values never pass
     * through the one fill() hydration Filament performs at mount —
     * applying the guesses here, at the moment the inputs that drive
     * them actually change, is what makes the spec'd
     * "defaults = guessMappings()" (§28.3) visible in the UI. Manual
     * edits survive until the user changes the file or the mode, which
     * is exactly when re-guessing is wanted.
     */
    protected function applyMappingGuesses(Get $get, Set $set): void
    {
        $definition = $this->currentDefinition($get('mode'));
        $headers = $this->headersFromFile($get('file'));

        $guesses = $headers === []
            ? []
            : app(ImportService::class)->guessMappings($definition, $headers);

        foreach ($definition->columns() as $column) {
            $set('columnMap.'.$column->key, $guesses[$column->key] ?? null);
        }
    }

    /** @return array<int, string> */
    protected function headersFromFile(mixed $file): array
    {
        $fileStore = app(ImportFileStore::class);
        $filePath = $fileStore->resolvePhysicalPath($file);

        if (blank($filePath)) {
            return [];
        }

        try {
            return app(ImportParser::class)
                ->parse($filePath, $fileStore->resolveOriginalName($file))['headers'];
        } catch (Throwable) {
            return [];
        }
    }
}
