<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\DirectTransfer;
use App\Models\DirectTransferItem;
use App\Models\InTransit;
use App\Models\LossLedger;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\ProductVariantUnitConversion;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\TransferRequisition;
use App\Models\TransferRequisitionItem;
use App\Models\TransferRequisitionItemRevision;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

if (! function_exists('exportTableQueryFor')) {
    /**
     * The model whose List page backs a given export registry key — the
     * query the action would hand the engine.
     */
    function exportTableQueryFor(string $key): Builder
    {
        return match (true) {
            str_starts_with($key, 'products.') => ProductVariant::query(),
            $key === 'warehouses' => Warehouse::query(),
            $key === 'suppliers' => Supplier::query(),
            $key === 'customers' => Customer::query(),
            $key === 'stock_movements' => StockMovement::query(),
            $key === 'loss_ledgers' => LossLedger::query(),
            str_starts_with($key, 'transfer_requisitions') => TransferRequisition::query(),
            $key === 'in_transits' => InTransit::query(),
            str_starts_with($key, 'purchase_orders') => PurchaseOrder::query(),
            str_starts_with($key, 'sales_orders') => SalesOrder::query(),
            str_starts_with($key, 'direct_transfers') => DirectTransfer::query(),
            default => throw new InvalidArgumentException("No table query mapped for [{$key}]."),
        };
    }
}

if (! function_exists('seedExportGraph')) {
    /**
     * Seed one complete instance of every exportable surface on a single
     * shared graph (one variant, one warehouse pair, one of each parent
     * header) so every child export is reachable from its parent.
     *
     * @return array<string, Model>
     */
    function seedExportGraph(): array
    {
        $variant = ProductVariant::factory()->create();
        $requisition = TransferRequisition::factory()->confirmed()->create();

        $item = TransferRequisitionItem::factory()->create([
            'transfer_requisition_id' => $requisition->getKey(),
            'product_variant_id' => $variant->getKey(),
        ]);

        $revision = TransferRequisitionItemRevision::factory()->create([
            'transfer_requisition_item_id' => $item->getKey(),
            'product_variant_id' => $variant->getKey(),
        ]);

        $purchaseOrder = PurchaseOrder::factory()->ordered()->create();
        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->getKey(),
            'product_variant_id' => $variant->getKey(),
        ]);

        $salesOrder = SalesOrder::factory()->confirmed()->create();
        SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->getKey(),
            'product_variant_id' => $variant->getKey(),
        ]);

        $directTransfer = DirectTransfer::factory()->create();
        DirectTransferItem::factory()->create([
            'direct_transfer_id' => $directTransfer->getKey(),
            'product_variant_id' => $variant->getKey(),
        ]);

        $conversion = ProductVariantUnitConversion::factory()->create([
            'product_variant_id' => $variant->getKey(),
        ]);

        $price = ProductVariantPrice::factory()->create([
            'product_variant_id' => $variant->getKey(),
        ]);

        StockMovement::factory()->create([
            'product_variant_id' => $variant->getKey(),
            'warehouse_id' => $requisition->from_warehouse_id,
        ]);

        InTransit::factory()->create([
            'transfer_requisition_id' => $requisition->getKey(),
            'transfer_requisition_item_id' => $item->getKey(),
            'product_variant_id' => $variant->getKey(),
        ]);

        LossLedger::factory()->create([
            'transfer_requisition_id' => $requisition->getKey(),
            'transfer_requisition_item_id' => $item->getKey(),
            'product_variant_id' => $variant->getKey(),
            'warehouse_id' => $requisition->from_warehouse_id,
        ]);

        return [
            'variant' => $variant,
            'requisition' => $requisition,
            'item' => $item,
            'revision' => $revision,
            'purchase_order' => $purchaseOrder,
            'sales_order' => $salesOrder,
            'direct_transfer' => $directTransfer,
            'conversion' => $conversion,
            'price' => $price,
            'supplier' => Supplier::factory()->create(),
            'customer' => Customer::factory()->create(),
        ];
    }
}
