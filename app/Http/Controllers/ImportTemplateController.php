<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Import\ImportService;
use App\Services\Import\ImportTemplateGenerator;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ImportTemplateController — serves the CSV/XLSX template download for
 * any registered import definition. The definition itself authorizes
 * the request server-side.
 */
class ImportTemplateController extends Controller
{
    public function download(string $import, string $format, ImportService $importService, ImportTemplateGenerator $generator): StreamedResponse
    {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        try {
            $definition = $importService->resolveDefinition($import);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        /** @var User $user */
        $user = auth()->user();

        $definition->authorize($user);

        return $generator->streamDownload($definition, $format);
    }
}
