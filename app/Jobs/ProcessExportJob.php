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
 * never trusted on its own, then reports the finished file back as a
 * database notification with its download link.
 *
 * The query itself travels as SQL plus bindings — the exact scoped and
 * filtered query the user was looking at — so the file matches the
 * table and the job never re-derives the user's warehouse scope from
 * whatever it happens to be at execution time.
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

            $fileStore->ensureUserDirectory((int) $this->user->getKey());

            $rows = $generator->generate(
                $definition,
                $this->sql,
                $this->bindings,
                $this->format,
                $fileStore->absolutePath((int) $this->user->getKey(), $this->filename),
            );

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
                        ->url(route('exports.download', ['file' => $this->filename])),
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
