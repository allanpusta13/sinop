<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Export\ExportFileStore;
use App\Services\Export\ExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * ExportDownloadController — serves a generated export file back to
 * the user who produced it. The file is always addressed inside the
 * authenticated user's own directory, so a filename can never be used
 * to reach another user's exports.
 */
class ExportDownloadController extends Controller
{
    public function __invoke(string $file, ExportFileStore $fileStore): BinaryFileResponse
    {
        if (! preg_match('/\A[A-Za-z0-9._-]+\z/', $file)) {
            abort(404);
        }

        /** @var User $user */
        $user = auth()->user();

        $path = $fileStore->absolutePath((int) $user->getKey(), $file);

        abort_unless($fileStore->exists((int) $user->getKey(), $file), 404);

        $format = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // Only the two formats the generator writes are ever served.
        abort_unless(in_array($format, ExportService::SUPPORTED_FORMATS, true), 404);

        return response()->download($path, $file, [
            'Content-Type' => match ($format) {
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                default => 'text/csv; charset=UTF-8',
            },
            // Stop a browser MIME-sniffing a CSV into something executable.
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
