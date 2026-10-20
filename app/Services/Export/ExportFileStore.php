<?php

declare(strict_types=1);

namespace App\Services\Export;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * ExportFileStore — private-disk storage concerns for the export
 * suite: generating unique per-user filenames, resolving physical
 * paths for the generator and the download route, and sweeping stale
 * files.
 *
 * Files live under `exports/{userId}` on the `local` disk; only the
 * owning user's directory is ever addressed, so an export file can
 * never be reached across users.
 */
class ExportFileStore
{
    public const DISK = 'local';

    public const DIRECTORY = 'exports';

    /**
     * Stale-file horizon in hours; files older than this are swept.
     */
    public const STALE_HOURS = 24;

    public function userDirectory(int $userId): string
    {
        return self::DIRECTORY.'/'.$userId;
    }

    /**
     * Allocate a unique filename inside the user's directory.
     */
    public function allocateFilename(int $userId, string $key, string $format): string
    {
        $diskKey = str_replace('.', '_', $key);

        return sprintf(
            'sinop_export_%s_%s_%s.%s',
            $diskKey,
            now()->format('Ymd_His'),
            str()->random(8),
            $format,
        );
    }

    /**
     * Physical filesystem path for a filename in the user's directory.
     */
    public function absolutePath(int $userId, string $filename): string
    {
        return Storage::disk(self::DISK)->path($this->userDirectory($userId).'/'.$filename);
    }

    /**
     * Whether the given filename exists in the user's directory.
     */
    public function exists(int $userId, string $filename): bool
    {
        return is_file($this->absolutePath($userId, $filename));
    }

    /**
     * Create the user's export directory if it is missing.
     */
    public function ensureUserDirectory(int $userId): void
    {
        File::ensureDirectoryExists($this->absolutePath($userId, ''));
    }

    /**
     * Delete every stale export file (older than STALE_HOURS) for the
     * user, except the absolute paths given in `$keep`. Idempotent.
     *
     * @param  array<int, string|null>  $keep
     */
    public function sweep(int $userId, array $keep = []): void
    {
        $directory = $this->absolutePath($userId, '');

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
     * Delete one file from the user's directory.
     *
     * Generation writes the header row before the first page is read,
     * so a failure part-way would otherwise leave a partial export
     * sitting on disk — download-able by its link until the next sweep.
     */
    public function discard(int $userId, string $filename): void
    {
        $path = $this->absolutePath($userId, $filename);

        if (is_file($path)) {
            File::delete($path);
        }
    }
}
