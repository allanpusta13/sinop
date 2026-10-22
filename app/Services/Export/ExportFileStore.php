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
     * Sidecar suffix recording the definition and warehouse scope a
     * generated file was written with.
     */
    public const META_SUFFIX = '.scope.json';

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
     * user. Fresh files are left alone, so a queued export waiting on
     * its own filename survives. Idempotent.
     */
    public function sweep(int $userId): void
    {
        $directory = $this->absolutePath($userId, '');

        if (! is_dir($directory)) {
            return;
        }

        $cutoff = now()->subHours(self::STALE_HOURS)->getTimestamp();

        foreach (File::files($directory) as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
            }
        }
    }

    /**
     * Record the definition key and warehouse scope a file was
     * generated with, so the download route can refuse it once the
     * user's scope has shrunk. Written next to the file; swept and
     * discarded with it.
     *
     * @param  array<int, int>|null  $scope  null = generated unrestricted
     */
    public function writeScopeMeta(int $userId, string $filename, string $definitionKey, ?array $scope): void
    {
        File::put($this->absolutePath($userId, $filename.self::META_SUFFIX), json_encode([
            'definition' => $definitionKey,
            'scope' => $scope,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * The definition and scope a file was generated with, or null when
     * the sidecar is missing or unreadable — nothing without provenance
     * is ever served.
     *
     * @return array{definition: string, scope: array<int, int>|null}|null
     */
    public function readScopeMeta(int $userId, string $filename): ?array
    {
        $path = $this->absolutePath($userId, $filename.self::META_SUFFIX);

        if (! is_file($path)) {
            return null;
        }

        $meta = json_decode((string) File::get($path), true);

        if (! is_array($meta) || ! is_string($meta['definition'] ?? null) || $meta['definition'] === '') {
            return null;
        }

        $scope = $meta['scope'] ?? null;

        return [
            'definition' => $meta['definition'],
            'scope' => is_array($scope) ? array_map('intval', array_values($scope)) : null,
        ];
    }

    /**
     * Delete one file from the user's directory, sidecar included.
     *
     * Generation writes the header row before the first page is read,
     * so a failure part-way would otherwise leave a partial export
     * sitting on disk — download-able by its link until the next sweep.
     */
    public function discard(int $userId, string $filename): void
    {
        foreach ([$filename, $filename.self::META_SUFFIX] as $candidate) {
            $path = $this->absolutePath($userId, $candidate);

            if (is_file($path)) {
                File::delete($path);
            }
        }
    }
}
