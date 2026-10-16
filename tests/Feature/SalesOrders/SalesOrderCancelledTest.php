<?php

declare(strict_types=1);

use App\Enums\SalesOrderStatus;
use App\Events\SalesOrderCancelled;
use App\Listeners\NotifySalesOrderCancelled;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

/**
 * §12 Pest coverage — `SalesOrderCancelledTest`.
 *
 * Blueprint §6.5 / §22.1 / §22.3a: `SalesService::cancelSalesOrder()`
 * writes `Cancelled` + `cancelled_at`, fires `SalesOrderCancelled`, and
 * `NotifySalesOrderCancelled` queues the §22.3b database notification.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(SalesService::class);
});

function makeCancellableOrder(SalesOrderStatus $status = SalesOrderStatus::Draft): SalesOrder
{
    return SalesOrder::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'warehouse_id' => Warehouse::factory()->create()->id,
        'ordered_by' => User::factory()->create()->id,
        'status' => $status,
    ]);
}

it('dispatches_event_on_cancel', function () {
    Event::fake([SalesOrderCancelled::class]);
    actingAsAdmin();

    $order = makeCancellableOrder(SalesOrderStatus::Draft);

    $this->service->cancelSalesOrder($order);

    Event::assertDispatched(
        SalesOrderCancelled::class,
        fn (SalesOrderCancelled $e): bool => $e->salesOrderId === $order->id,
    );
});

it('writes the Cancelled status and cancelled_at', function () {
    actingAsAdmin();
    $order = makeCancellableOrder(SalesOrderStatus::Confirmed);

    $this->service->cancelSalesOrder($order);

    $fresh = $order->fresh();
    expect($fresh->status)->toBe(SalesOrderStatus::Cancelled);
    expect($fresh->cancelled_at)->not->toBeNull();
});

it('listener_queues_notification', function () {
    // §22.3a — NotifySalesOrderCancelled is a ShouldQueue database
    // notification to admins plus staff at the cancelling warehouse.
    expect(NotifySalesOrderCancelled::class)
        ->toImplement(ShouldQueue::class);
});
