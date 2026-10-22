<?php

declare(strict_types=1);

use App\Models\Supplier;

if (! function_exists('supplierImportTempCsv')) {
    /**
     * Write a temp CSV with the supplier template headers (Name,
     * Contact Person, Phone, Email, Address) for the given rows and
     * return its physical path.
     *
     * @param  array<int, array{0: string, 1: string, 2: string, 3: string, 4: string}>  $rows
     */
    function supplierImportTempCsv(array $rows): string
    {
        $lines = ['Name,Contact Person,Phone,Email,Address'];

        foreach ($rows as $row) {
            $lines[] = sprintf('"%s","%s","%s","%s","%s"', $row[0], $row[1], $row[2], $row[3], $row[4]);
        }

        $path = tempnam(sys_get_temp_dir(), 'sinop_supplier_import_').'.csv';
        file_put_contents($path, implode("\n", $lines));

        return $path;
    }
}

if (! function_exists('supplierImportColumnMap')) {
    /**
     * The full supplier column map, keyed by column and mapped onto the
     * localized template headers.
     *
     * @return array<string, string>
     */
    function supplierImportColumnMap(): array
    {
        return [
            'name' => __('resources.suppliers.fields.name'),
            'contact_person' => __('resources.suppliers.fields.contact_person'),
            'phone' => __('resources.suppliers.fields.phone'),
            'email' => __('resources.suppliers.fields.email'),
            'address' => __('resources.suppliers.fields.address'),
        ];
    }
}

if (! function_exists('createSoftDeletedSupplier')) {
    /** Create a soft-deleted supplier with the given name. */
    function createSoftDeletedSupplier(string $name): Supplier
    {
        $supplier = Supplier::factory()->create(['name' => $name]);
        $supplier->delete();

        return Supplier::query()->withTrashed()->findOrFail($supplier->getKey());
    }
}
