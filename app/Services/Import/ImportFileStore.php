<?php

declare(strict_types=1);

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * ImportFileStore — private-disk storage concerns for the import
 * suite: resolving Livewire upload state to physical paths (only ever
 * inside the authenticated user's own directory), freezing the
 * preview JSON per user, sweeping stale leftovers on the shared
 * 24-hour horizon, and cleaning up the files of a finished run.
 *
 * Files live under `private/imports/{userId}` on the `local` disk with
 * generated (hash) names tied to the uploading user; the frozen
 * preview is stored next to the source file as `preview.json`.
 *
 * Cleanup is per-run, never per-user-directory: a second import of the
 * same user (another tab, or a queued job still waiting on its source)
 * shares this directory, and wiping it wholesale would delete a
 * sibling run's file mid-flight. Anything a run does not clean up
 * ages into sweep().
 */
class ImportFileStore
{
    public const DISK = 'local';

    public const DIRECTORY = 'imports';

    public const PREVIEW_FILENAME = 'preview.json';

    /**
     * Stale-file horizon in hours; files older than this are swept.
     */
    public const STALE_HOURS = 24;

    public function userDirectory(int $userId): string
    {
        return self::DIRECTORY.'/'.$userId;
    }

    public function previewPath(int $userId): string
    {
        return Storage::disk(self::DISK)->path($this->userDirectory($userId).'/'.self::PREVIEW_FILENAME);
    }

    /**
     * Resolve arbitrary Livewire/Filament file-upload state (temp file,
     * array wrapper or stored path) to a physical path.
     *
     * String state is client-mutable (it arrives in the Livewire
     * payload), so it is never trusted as a path: it only resolves when
     * it realpath-contains inside the *authenticated* user's own import
     * directory. Traversal (`..`), absolute paths, other users'
     * directories and paths outside the disk all resolve to null —
     * otherwise a tampered `file` property would turn the mapping step
     * into an arbitrary file read (the headers are echoed back as
     * select options).
     */
    public function resolvePhysicalPath(mixed $file): ?string
    {
        if (is_array($file)) {
            $file = Arr::first($file);
        }

        if ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile) {
            return $file->getRealPath();
        }

        if (! is_string($file) || $file === '') {
            return null;
        }

        $ownerId = auth()->id();

        if ($ownerId === null) {
            return null;
        }

        $directory = realpath(Storage::disk(self::DISK)->path($this->userDirectory((int) $ownerId)));
        $candidate = realpath(Storage::disk(self::DISK)->path($file));

        if ($directory === false || $candidate === false) {
            return null;
        }

        if (! str_starts_with($candidate, $directory.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $candidate;
    }

    /**
     * Resolve arbitrary Livewire/Filament file-upload state to the
     * original client filename.
     */
    public function resolveOriginalName(mixed $file): ?string
    {
        if (is_array($file)) {
            $file = Arr::first($file);
        }

        if ($file instanceof TemporaryUploadedFile) {
            return $file->getClientOriginalName();
        }

        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalName();
        }

        if (filled($file) && is_string($file)) {
            return basename($file);
        }

        return null;
    }

    /**
     * Freeze a computed preview next to the user's import files.
     *
     * @param  array<string, mixed>  $preview
     */
    public function writePreview(int $userId, array $preview): void
    {
        $path = $this->previewPath($userId);

        File::ensureDirectoryExists(dirname($path));

        try {
            File::put($path, json_encode($preview, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        } catch (JsonException $exception) {
            throw new \RuntimeException(__('imports.errors.unreadable_file'), 0, $exception);
        }
    }

    /** @return array<string, mixed>|null */
    public function readPreview(int $userId): ?array
    {
        $path = $this->previewPath($userId);

        if (! file_exists($path)) {
            return null;
        }

        try {
            $decoded = json_decode((string) File::get($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Delete every stale file (older than STALE_HOURS) in the user's
     * import directory except the physical paths given in `$keep` (the
     * currently referenced source file). Fresh files are left alone so a
     * queued import's source and a just-staged upload survive the sweep.
     * Idempotent.
     *
     * @param  array<int, string|null>  $keep
     */
    public function sweep(int $userId, array $keep = []): void
    {
        $directory = Storage::disk(self::DISK)->path($this->userDirectory($userId));

        if (! is_dir($directory)) {
            return;
        }

        $keep = array_filter(array_map(
            static fn ($path): ?string => filled($path) ? (realpath((string) $path) ?: null) : null,
            $keep,
        ));

        $cutoff = now()->subHours(self::STALE_HOURS)->getTimestamp();

        foreach (File::files($directory) as $file) {
            $physical = $file->getPathname();

            if (in_array((string) (realpath($physical) ?: $physical), $keep, true)) {
                continue;
            }

            if ($file->getMTime() < $cutoff) {
                File::delete($physical);
            }
        }
    }

    /**
     * Clean up after one finished run: its source file (only when it
     * lies inside the user's own directory) plus the frozen preview.
     * Sibling files in the same directory — another import queued or
     * staged in a second tab — are left alone and age into sweep().
     *
     * A concurrent session that still holds this preview recovers on
     * submit through the `preview_expired` flow and re-freezes it.
     */
    public function cleanupRun(int $userId, string $physicalPath): void
    {
        $directory = realpath(Storage::disk(self::DISK)->path($this->userDirectory($userId)));
        $candidate = realpath($physicalPath);

        if ($directory !== false
            && $candidate !== false
            && str_starts_with($candidate, $directory.DIRECTORY_SEPARATOR)
            && is_file($candidate)) {
            File::delete($candidate);
        }

        File::delete($this->previewPath($userId));
    }
}
