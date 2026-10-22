<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\Customer;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;

/**
 * CustomerExportDefinition — "customers" (#225): the Customers List
 * page's own rows, including the sales-order counter the table shows.
 */
class CustomerExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'customers';
    }

    public function labelKey(): string
    {
        return 'exports.types.customers';
    }

    public function modelClass(): string
    {
        return Customer::class;
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'name', labelKey: 'resources.customers.table.name'),
            new ExportColumn(key: 'contact_person', labelKey: 'resources.customers.table.contact'),
            new ExportColumn(key: 'phone', labelKey: 'resources.customers.table.phone'),
            new ExportColumn(key: 'email', labelKey: 'resources.customers.table.email'),
            new ExportColumn(key: 'address', labelKey: 'resources.customers.fields.address'),
            new ExportColumn(key: 'is_active', labelKey: 'resources.customers.table.status', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'sales_orders_count', labelKey: 'resources.customers.table.sales_orders', cast: ExportColumn::CAST_INT),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return $tableQuery
            ->select([
                'customers.name',
                'customers.contact_person',
                'customers.phone',
                'customers.email',
                'customers.address',
                'customers.is_active',
            ])
            ->withCount('salesOrders');
    }
}
