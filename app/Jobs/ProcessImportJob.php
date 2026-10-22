<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\DomainErrorException;
use App\Models\User;
use App\Services\Import\ImportFileStore;
use App\Services\Import\ImportService;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * ProcessImportJob — generic queued execution for imports above the
 * inline row threshold. Re-authorizes and re-validates server-side, so
 * the preview is never trusted, then reports the outcome back as a
 * database notification. On success (and on a final authorization
 * loss) this run's source file and frozen preview are cleaned up;
 * other failures keep them so the user can retry, and the age-gated
 * sweep collects whatever is eventually left behind.
 */
class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * A large import may legitimately run for minutes; the worker's
     * default 60s would SIGTERM it mid-flight — past the point the
     * header/side effects exist but before any notification could be
     * sent. One attempt only: the expected counts travel with the job,
     * so a blind retry against a drifted database would only fail into
     * the mismatch flow after already notifying once.
     */
    public int $timeout = 300;

    public int $tries = 1;

    /**
     * @param  array<string, string|int|null>  $columnMap
     * @param  array{create: int, update: int, reject: int, total?: int}  $expectedCounts
     */
    public function __construct(
        public readonly string $filePath,
        public readonly ?string $originalName,
        public readonly string $definitionKey,
        public readonly array $columnMap,
        public readonly array $expectedCounts,
        public readonly bool $confirmUpdates,
        public readonly int $userId,
    ) {}

    public function handle(ImportService $importService, ImportFileStore $fileStore): void
    {
        $user = User::findOrFail($this->userId);

        $release = false;

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

            $release = true;

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
            $release = true;

            Notification::make()
                ->danger()
                ->title(__($exception->translationKey().'.title'))
                ->body(__($exception->translationKey().'.body', $exception->context()))
                ->sendToDatabase($user);

            throw $exception;
        } catch (Throwable $exception) {
            // Losing authorization is final, so the source file goes with the
            // run. Anything else stays on disk for the user's next attempt,
            // and it ages into the stale sweep once the user next opens the
            // wizard (nothing sweeps on a schedule in this app).
            $release = $release || $exception instanceof AuthorizationException;

            Notification::make()
                ->danger()
                ->title(__('imports.notifications.failed_title'))
                ->body(__('imports.notifications.failed_body'))
                ->sendToDatabase($user);

            throw $exception;
        } finally {
            if ($release) {
                $fileStore->cleanupRun($this->userId, $this->filePath);
            }
        }
    }
}
