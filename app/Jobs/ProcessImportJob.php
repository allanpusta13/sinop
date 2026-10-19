<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\DomainErrorException;
use App\Models\User;
use App\Services\Import\ImportFileStore;
use App\Services\Import\ImportService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * ProcessImportJob — generic queued execution for imports above the
 * inline row threshold. Re-authorizes and re-validates server-side, so
 * the preview is never trusted, then reports the final counts back as
 * a database notification and cleans the user's import files up.
 */
class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, string|int|null>  $columnMap
     * @param  array{create: int, update: int, reject: int, total?: int}  $expectedCounts
     */
    public function __construct(
        public string $filePath,
        public ?string $originalName,
        public string $definitionKey,
        public array $columnMap,
        public array $expectedCounts,
        public bool $confirmUpdates,
        public int $userId,
    ) {
        $this->onConnection(config('queue.import_connection', config('queue.default')));
    }

    public function handle(ImportService $importService, ImportFileStore $fileStore): void
    {
        $user = User::findOrFail($this->userId);

        try {
            $counts = $importService->executeTransactional(
                $this->filePath,
                $this->originalName,
                $this->definitionKey,
                $this->columnMap,
                $this->expectedCounts,
                $this->confirmUpdates,
                $user,
            );

            Notification::make()
                ->success()
                ->title(__('imports.notifications.completed_title'))
                ->body(__('imports.notifications.completed_body', [
                    'created' => $counts['created'],
                    'updated' => $counts['updated'],
                    'rejected' => $counts['rejected'],
                    'skipped' => $counts['skipped'],
                ]))
                ->sendToDatabase($user);
        } catch (DomainErrorException $exception) {
            Notification::make()
                ->danger()
                ->title(__($exception->translationKey().'.title'))
                ->body(__($exception->translationKey().'.body', $exception->context()))
                ->sendToDatabase($user);

            throw $exception;
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title(__('imports.notifications.failed_title'))
                ->body($exception->getMessage())
                ->sendToDatabase($user);

            throw $exception;
        } finally {
            $fileStore->cleanupUser($this->userId);
        }
    }
}
