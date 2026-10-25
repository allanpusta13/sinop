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
    $appDir = base_path('app');
    $testsDir = base_path('tests');
    $configDir = base_path('config');
    $databaseDir = base_path('database');
    $resourcesDir = base_path('resources');

    $searchPattern = 'ProductExporter';

    $cmd = sprintf(
        'grep -rn %s %s %s %s %s %s 2>/dev/null',
        escapeshellarg($searchPattern),
        escapeshellarg($appDir),
        escapeshellarg($testsDir),
        escapeshellarg($configDir),
        escapeshellarg($databaseDir),
        escapeshellarg($resourcesDir)
    );

    $output = trim(shell_exec($cmd) ?? '');

    // Exclude this test file itself from the match count
    $matches = array_filter(
        explode("\n", $output),
        fn (string $line) => $line !== '' && ! str_contains($line, 'ProductExporterDeletionTest.php')
    );

    expect($matches)->toBeEmpty();
});
