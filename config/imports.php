<?php

declare(strict_types=1);
use App\Services\Import\Definitions\CustomerImportDefinition;
use App\Services\Import\Definitions\ProductFamilyImportDefinition;
use App\Services\Import\Definitions\ProductVariantImportDefinition;
use App\Services\Import\Definitions\SupplierImportDefinition;
use App\Services\Import\Definitions\WarehouseImportDefinition;

/*
|--------------------------------------------------------------------------
| Import suite registry
|--------------------------------------------------------------------------
|
| Maps import definition registry keys (e.g. "products.families") to
| their ImportDefinition classes. The shared engine resolves keys from
| this map for the wizard actions, the preview/execution services and
| the template download route; unknown keys 404.
|
*/

return [

    'definitions' => [
        'products.families' => ProductFamilyImportDefinition::class,
        'products.variants' => ProductVariantImportDefinition::class,
        'warehouses' => WarehouseImportDefinition::class,
        'suppliers' => SupplierImportDefinition::class,
        'customers' => CustomerImportDefinition::class,
    ],

];
