<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

/**
 * §207 ProductExporter Deletion Regression Tests
 */
it('product_exporter_does_not_exist', function () {
    $exporterClass = 'App\\Filament\\Exports\\ProductExporter';
    $exporterFile = base_path('app/Filament/Exports/ProductExporter.php');

    expect(class_exists($exporterClass))->toBeFalse();
    expect(file_exists($exporterFile))->toBeFalse();
});

it('no_reference_to_product_exporter', function () {
    $searchDirs = [
        base_path('app'),
        base_path('tests'),
        base_path('config'),
        base_path('database'),
        base_path('resources'),
    ];

    $searchPattern = 'ProductExporter';

    // PHP-native recursive scan instead of `grep -rn ... 2>/dev/null`.
    // The POSIX redirect is not understood by cmd.exe, which made the shell
    // version fail to execute at all on Windows (silent vacuous pass) while
    // leaking "The system cannot find the path specified." into the output.
    $candidateLines = [];

    foreach ($searchDirs as $searchDir) {
        if (! is_dir($searchDir)) {
            continue;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($searchDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $pathname = $file->getPathname();
            $contents = @file_get_contents($pathname);

            if ($contents === false || str_contains($contents, "\0")) {
                continue;
            }

            $lines = preg_split('/\R/', $contents) ?: [];

            foreach ($lines as $index => $line) {
                if (! str_contains($line, $searchPattern)) {
                    continue;
                }

                $candidateLines[] = $pathname.':'.($index + 1).':'.$line;
            }
        }
    }

    // Exclude this test file itself from the match count
    $matches = array_filter(
        $candidateLines,
        fn (string $line) => $line !== '' && ! str_contains($line, 'ProductExporterDeletionTest.php')
    );

    expect($matches)->toBeEmpty();
});
