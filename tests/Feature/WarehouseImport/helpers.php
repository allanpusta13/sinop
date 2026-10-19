<?php

declare(strict_types=1);

if (! function_exists('warehouseImportTempCsv')) {
    /**
     * Write content to a temp CSV file and return its physical path.
     */
    function warehouseImportTempCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sinop_warehouse_import_test_').'.csv';
        file_put_contents($path, $content);

        return $path;
    }
}

if (! function_exists('warehouseImportCsv')) {
    /**
     * Write a temp CSV with Code,Name,Location headers for the given
     * [code, name, location] rows and return its physical path.
     *
     * @param  array<int, array{0: string, 1: string, 2: string}>  $rows
     */
    function warehouseImportCsv(array $rows): string
    {
        $lines = ['Code,Name,Location'];

        foreach ($rows as [$code, $name, $location]) {
            $lines[] = sprintf('"%s","%s","%s"', $code, $name, $location);
        }

        return warehouseImportTempCsv(implode("\n", $lines));
    }
}

if (! function_exists('warehouseImportColumnMap')) {
    /**
     * The full warehouse column map (all template columns mapped).
     *
     * @return array<string, string>
     */
    function warehouseImportColumnMap(): array
    {
        return ['code' => 'Code', 'name' => 'Name', 'location' => 'Location'];
    }
}
