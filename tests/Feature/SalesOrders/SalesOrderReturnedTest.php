<?php

declare(strict_types=1);

use App\Enums\SalesOrderStatus;
use App\Enums\StockMovementType;
use App\Events\SalesOrderReturned;
use App\Listeners\NotifySalesOrderReturned;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

/**
 * §12 Pest coverage — `SalesOrderReturnedTest`.
 *
 * Blueprint §6.5 / §22.1 / §22.3a: `SalesService::recordSalesReturn()`
 * writes a `SaleReturn` movement and fires `SalesOrderReturned`;
 * `NotifySalesOrderReturned` queues the §22.3b notification.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(SalesService::class);
});

/**
 * A dispatched order with one item carrying `$dispatched` base units.
 *
 * @return array{order: SalesOrder, item: SalesOrderItem}
 */
function makeReturnableOrder(int $dispatched = 10): array
{
    $order = SalesOrder::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'warehouse_id' => Warehouse::factory()->create()->id,
        'ordered_by' => User::factory()->create()->id,
        'status' => SalesOrderStatus::Dispatched,
    ]);

    $item = SalesOrderItem::factory()->create([
        'sales_order_id' => $order->id,
        'product_variant_id' => ProductVariant::factory()->create(['base_unit_name' => 'pc'])->id,
        'unit_name' => 'pc',
        'unit_ratio' => 1,
        'qty' => $dispatched,
        'base_qty' => $dispatched,
        'unit_sale_price_snapshot' => '1.0000',
        'dispatched_base_qty' => $dispatched,
    ]);

    return ['order' => $order, 'item' => $item];
}

it('dispatches_event_on_return', function () {
    Event::fake([SalesOrderReturned::class]);
    actingAsAdmin();

    ['order' => $order, 'item' => $item] = makeReturnableOrder();

    $this->service->recordSalesReturn((int) $item->id, 3);

    Event::assertDispatched(
        SalesOrderReturned::class,
        fn (SalesOrderReturned $e): bool => $e->salesOrderId === $order->id,
    );
});

it('writes a SaleReturn stock movement', function () {
    actingAsAdmin();
    ['order' => $order, 'item' => $item] = makeReturnableOrder();

    $this->service->recordSalesReturn((int) $item->id, 4);

    $movement = StockMovement::query()
        ->where('type', StockMovementType::SaleReturn)
        ->where('reference_id', (string) $item->id)
        ->first();

    expect($movement)->not->toBeNull();
    expect((int) $movement->quantity)->toBe(4);
    expect($movement->warehouse_id)->toBe($order->warehouse_id);
});

it('listener_queues_notification', function () {
    // §22.3a — NotifySalesOrderReturned is a ShouldQueue notification.
    expect(NotifySalesOrderReturned::class)
        ->toImplement(ShouldQueue::class);
});
