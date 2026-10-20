<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\Supplier;
use App\Models\User;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * SupplierExportDefinition — "suppliers" (#225): the Suppliers List
 * page's own rows, including the open purchase-order counter the
 * table shows.
 */
class SupplierExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'suppliers';
    }

    public function labelKey(): string
    {
        return 'exports.types.suppliers';
    }

    public function modelClass(): string
    {
        return Supplier::class;
    }

    public function authorize(User $user): void
    {
        Gate::forUser($user)->authorize('viewAny', Supplier::class);
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'name', labelKey: 'resources.suppliers.table.name'),
            new ExportColumn(key: 'contact_person', labelKey: 'resources.suppliers.table.contact'),
            new ExportColumn(key: 'phone', labelKey: 'resources.suppliers.table.phone'),
            new ExportColumn(key: 'email', labelKey: 'resources.suppliers.table.email'),
            new ExportColumn(key: 'address', labelKey: 'resources.suppliers.fields.address'),
            new ExportColumn(key: 'is_active', labelKey: 'resources.suppliers.table.status', cast: ExportColumn::CAST_INT),
            new ExportColumn(key: 'purchase_orders_count', labelKey: 'resources.suppliers.table.purchase_orders', cast: ExportColumn::CAST_INT),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return $tableQuery
            ->select([
                'suppliers.name',
                'suppliers.contact_person',
                'suppliers.phone',
                'suppliers.email',
                'suppliers.address',
                'suppliers.is_active',
            ])
            ->withCount('purchaseOrders');
    }
}
