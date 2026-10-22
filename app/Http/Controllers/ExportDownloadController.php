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
 * to reach another user's exports, and it is only served while the
 * warehouse scope it was generated with is still the one the user
 * holds.
 */
class ExportDownloadController extends Controller
{
    public function __invoke(string $file, ExportFileStore $fileStore, ExportService $exportService): BinaryFileResponse
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

        // Every generated file carries a sidecar recording the
        // definition and warehouse scope it was written with. The file
        // is only served while that scope is still held: a warehouse
        // taken away since generation must not keep leaking through a
        // link that lives for up to 24 hours.
        $meta = $fileStore->readScopeMeta((int) $user->getKey(), $file);

        abort_unless($meta !== null, 404);

        $currentScope = $exportService->currentWarehouseScope(
            $exportService->resolveDefinition($meta['definition']),
            $user,
        );

        $stillHeld = $currentScope === null
            || ($meta['scope'] !== null && array_diff($meta['scope'], $currentScope) === []);

        abort_unless($stillHeld, 404);

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
