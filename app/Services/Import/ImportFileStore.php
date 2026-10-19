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
 * suite: resolving Livewire upload state to physical paths, freezing
 * the preview JSON per user, and cleaning user import files up when an
 * import finishes or is cancelled.
 *
 * Files live under `private/imports/{userId}` on the `local` disk with
 * generated (hash) names tied to the uploading user; the frozen
 * preview is stored next to the source file as `preview.json`.
 */
class ImportFileStore
{
    public const DISK = 'local';

    public const DIRECTORY = 'imports';

    public const PREVIEW_FILENAME = 'preview.json';

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
     */
    public function resolvePhysicalPath(mixed $file): ?string
    {
        if (is_array($file)) {
            $file = Arr::first($file);
        }

        if ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile) {
            return $file->getRealPath();
        }

        if (filled($file) && is_string($file)) {
            $diskPath = Storage::disk(self::DISK)->path($file);

            if (file_exists($diskPath)) {
                return $diskPath;
            }

            if (file_exists($file)) {
                return $file;
            }
        }

        return null;
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
     * Delete every file in the user's import directory except the
     * physical paths given in `$keep` (the currently referenced source
     * file). Idempotent.
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

        foreach (File::files($directory) as $file) {
            $physical = $file->getPathname();

            if (in_array((string) (realpath($physical) ?: $physical), $keep, true)) {
                continue;
            }

            File::delete($physical);
        }
    }

    /**
     * Delete the user's whole import directory (source files, frozen
     * preview). Called when an import finishes or is cancelled.
     */
    public function cleanupUser(int $userId): void
    {
        $directory = Storage::disk(self::DISK)->path($this->userDirectory($userId));

        if (is_dir($directory)) {
            File::deleteDirectory($directory);
        }
    }
}
