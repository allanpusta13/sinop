<?php

declare(strict_types=1);

use App\Models\Customer;

if (! function_exists('customerImportTempCsv')) {
    /**
     * Write a temp CSV with the customer template headers (Name,
     * Contact Person, Phone, Email, Address) for the given rows and
     * return its physical path.
     *
     * @param  array<int, array{0: string, 1: string, 2: string, 3: string, 4: string}>  $rows
     */
    function customerImportTempCsv(array $rows): string
    {
        $lines = ['Name,Contact Person,Phone,Email,Address'];

        foreach ($rows as $row) {
            $lines[] = sprintf('"%s","%s","%s","%s","%s"', $row[0], $row[1], $row[2], $row[3], $row[4]);
        }

        $path = tempnam(sys_get_temp_dir(), 'sinop_customer_import_').'.csv';
        file_put_contents($path, implode("\n", $lines));

        return $path;
    }
}

if (! function_exists('customerImportColumnMap')) {
    /**
     * The full customer column map, keyed by column and mapped onto the
     * localized template headers.
     *
     * @return array<string, string>
     */
    function customerImportColumnMap(): array
    {
        return [
            'name' => __('resources.customers.fields.name'),
            'contact_person' => __('resources.customers.fields.contact_person'),
            'phone' => __('resources.customers.fields.phone'),
            'email' => __('resources.customers.fields.email'),
            'address' => __('resources.customers.fields.address'),
        ];
    }
}

if (! function_exists('createSoftDeletedCustomer')) {
    /** Create a soft-deleted customer with the given name. */
    function createSoftDeletedCustomer(string $name): Customer
    {
        $customer = Customer::factory()->create(['name' => $name]);
        $customer->delete();

        return Customer::query()->withTrashed()->findOrFail($customer->getKey());
    }
}
