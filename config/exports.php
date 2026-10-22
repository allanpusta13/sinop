<?php

declare(strict_types=1);

use App\Services\Export\Definitions\CurrentPriceExportDefinition;
use App\Services\Export\Definitions\CustomerExportDefinition;
use App\Services\Export\Definitions\DirectTransferExportDefinition;
use App\Services\Export\Definitions\DirectTransferItemExportDefinition;
use App\Services\Export\Definitions\InTransitExportDefinition;
use App\Services\Export\Definitions\LossLedgerExportDefinition;
use App\Services\Export\Definitions\ProductFamilyExportDefinition;
use App\Services\Export\Definitions\ProductVariantExportDefinition;
use App\Services\Export\Definitions\PurchaseOrderExportDefinition;
use App\Services\Export\Definitions\PurchaseOrderItemExportDefinition;
use App\Services\Export\Definitions\SalesOrderExportDefinition;
use App\Services\Export\Definitions\SalesOrderItemExportDefinition;
use App\Services\Export\Definitions\StockMovementExportDefinition;
use App\Services\Export\Definitions\SupplierExportDefinition;
use App\Services\Export\Definitions\TransferRequisitionExportDefinition;
use App\Services\Export\Definitions\TransferRequisitionItemExportDefinition;
use App\Services\Export\Definitions\TransferRequisitionItemRevisionExportDefinition;
use App\Services\Export\Definitions\UnitConversionExportDefinition;
use App\Services\Export\Definitions\WarehouseExportDefinition;

return [

    /*
    |--------------------------------------------------------------------------
    | Export definitions (#225 / #226)
    |--------------------------------------------------------------------------
    |
    | One entry per exportable type. Keys are stable identifiers used by
    | the resource actions, the queued job and the generated filename;
    | values are the definition classes resolved by ExportService.
    |
    */

    'definitions' => [

        // --- #225 — Master data ---
        'products.families' => ProductFamilyExportDefinition::class,
        'products.variants' => ProductVariantExportDefinition::class,
        'products.current_prices' => CurrentPriceExportDefinition::class,
        'products.unit_conversions' => UnitConversionExportDefinition::class,
        'warehouses' => WarehouseExportDefinition::class,
        'suppliers' => SupplierExportDefinition::class,
        'customers' => CustomerExportDefinition::class,

        // --- #226 — Transactions and stock ledger ---
        'stock_movements' => StockMovementExportDefinition::class,
        'loss_ledgers' => LossLedgerExportDefinition::class,
        'transfer_requisitions' => TransferRequisitionExportDefinition::class,
        'transfer_requisitions.items' => TransferRequisitionItemExportDefinition::class,
        'transfer_requisitions.revisions' => TransferRequisitionItemRevisionExportDefinition::class,
        'in_transits' => InTransitExportDefinition::class,
        'purchase_orders' => PurchaseOrderExportDefinition::class,
        'purchase_orders.items' => PurchaseOrderItemExportDefinition::class,
        'sales_orders' => SalesOrderExportDefinition::class,
        'sales_orders.items' => SalesOrderItemExportDefinition::class,
        'direct_transfers' => DirectTransferExportDefinition::class,
        'direct_transfers.items' => DirectTransferItemExportDefinition::class,
    ],

];
