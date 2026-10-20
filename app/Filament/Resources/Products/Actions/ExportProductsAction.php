<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Actions;

use App\Filament\Support\Exports\ExportRecordsAction;

/**
 * ExportProductsAction — export header action on ListProducts
 * (#225). Offers the four product surfaces the Products page backs:
 * families, variants, current prices and unit conversions. The export
 * runs against the page's own filtered query, so the family and
 * conversion files follow whatever the user has filtered on screen.
 */
class ExportProductsAction extends ExportRecordsAction
{
    protected function name(): string
    {
        return 'exportProducts';
    }

    /**
     * @return array<int, string>
     */
    protected function definitionKeys(): array
    {
        return [
            'products.families',
            'products.variants',
            'products.current_prices',
            'products.unit_conversions',
        ];
    }
}
