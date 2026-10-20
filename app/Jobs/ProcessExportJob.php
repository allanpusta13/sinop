<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\Export\ExportFileStore;
use App\Services\Export\ExportGenerator;
use App\Services\Export\ExportService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use Throwable;

/**
 * ProcessExportJob — generic queued execution for exports above the
 * inline row threshold. Re-authorizes server-side so the snapshot is
 * never trusted on its own, re-narrows the snapshot against the
 * warehouses the user holds at execution time, and reports the
 * finished file back as a database notification with its download
 * link.
 *
 * The query travels as SQL plus bindings — the exact scoped and
 * filtered query the user was looking at. Scope is the one thing
 * deliberately *not* frozen with it: it is re-derived here, so a
 * warehouse taken away while the job waited shrinks the file and one
 * granted does not grow it (§28.12).
 */
class ProcessExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, mixed>  $bindings
     */
    public function __construct(
        public readonly string $sql,
        public readonly array $bindings,
        public readonly string $definitionKey,
        public readonly string $format,
        public readonly string $filename,
        public readonly User $user,
    ) {}

    public function handle(ExportService $service, ExportGenerator $generator, ExportFileStore $fileStore): void
    {
        try {
            $definition = $service->resolveDefinition($this->definitionKey);
            $definition->authorize($this->user);

            // The filename travelled serialised through the queue, so it is
            // re-checked here rather than trusted — a planted payload must
            // not be able to write outside the user's own directory.
            if (preg_match('/\A[A-Za-z0-9._-]+\z/', $this->filename) !== 1) {
                throw new InvalidArgumentException(__('exports.errors.invalid_filename'));
            }

            $userId = (int) $this->user->getKey();

            // Scope is re-derived rather than trusted from the snapshot:
            // the assignment the export was dispatched with may no longer
            // be the one the user holds. It may only narrow while the job
            // waits, so what is left is intersected with the snapshot —
            // and a user with nothing left is told plainly and handed no
            // file at all.
            $scope = $service->currentWarehouseScope($definition, $this->user);

            if ($scope === []) {
                Notification::make()
                    ->danger()
                    ->title(__('exports.notifications.scope_lost_title'))
                    ->body(__('exports.notifications.scope_lost_body', [
                        'type' => __($definition->labelKey()),
                    ]))
                    ->sendToDatabase($this->user);

                return;
            }

            [$sql, $bindings] = $scope === null
                ? [$this->sql, $this->bindings]
                : $service->narrowToScope($this->sql, $this->bindings, $definition, $scope);

            $fileStore->ensureUserDirectory($userId);
            $path = $fileStore->absolutePath($userId, $this->filename);

            try {
                $rows = $generator->generate($definition, $sql, $bindings, $this->format, $path);
            } catch (Throwable $exception) {
                // The header row is already on disk by now, and a
                // half-written file would still be served by its own
                // download link until the next sweep.
                $fileStore->discard($userId, $this->filename);

                throw $exception;
            }

            Notification::make()
                ->success()
                ->title(__('exports.notifications.completed_title'))
                ->body(__('exports.notifications.completed_body', [
                    'rows' => number_format($rows),
                    'type' => __($definition->labelKey()),
                ]))
                ->actions([
                    Action::make('download')
                        ->label(__('exports.notifications.download_label'))
                        // Same SPA-mode escape hatch as ExportRecordsAction:
                        // `wire:navigate` would fetch the CSV into the page
                        // instead of downloading it (see ImportWizardAction).
                        ->url(route('exports.download', ['file' => $this->filename]), shouldOpenInNewTab: true),
                ])
                ->sendToDatabase($this->user);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->danger()
                ->title(__('exports.notifications.failed_title'))
                ->body(__('exports.notifications.failed_body'))
                ->sendToDatabase($this->user);
        }
    }
}
