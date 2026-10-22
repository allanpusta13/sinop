<?php

declare(strict_types=1);

namespace App\Services\Export\Definitions;

use App\Models\Product;
use App\Services\Export\ExportColumn;
use App\Services\Export\ExportDefinition;
use Illuminate\Database\Eloquent\Builder;

/**
 * ProductFamilyExportDefinition — "products.families" (#225): one row
 * per parent family behind the variants the user is looking at.
 *
 * There is no families List page (ProductResource lists variants), so
 * the family set is derived from the table query: the export contains
 * exactly the families present in the current view, filtered and
 * warehouse-scoped like everything else on the page. Families with no
 * variants are therefore not reachable — same as on screen.
 */
class ProductFamilyExportDefinition extends ExportDefinition
{
    public function key(): string
    {
        return 'products.families';
    }

    public function labelKey(): string
    {
        return 'exports.types.products.families';
    }

    public function modelClass(): string
    {
        return Product::class;
    }

    /**
     * @return array<int, ExportColumn>
     */
    public function columns(): array
    {
        return [
            new ExportColumn(key: 'name', labelKey: 'resources.products.fields.family_name'),
            new ExportColumn(key: 'category', labelKey: 'resources.products.fields.family_category'),
        ];
    }

    public function query(Builder $tableQuery): Builder
    {
        return Product::query()
            ->select(['products.name', 'products.category'])
            ->whereIn('products.id', $this->parentIdsQuery($tableQuery)
                ->select('product_variants.product_id'));
    }
}
