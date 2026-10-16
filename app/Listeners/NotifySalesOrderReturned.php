<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\SalesOrderReturned;
use App\Models\SalesOrder;
use App\Models\User;
use App\Notifications\SalesOrderReturnedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * NotifySalesOrderReturned — §22.3a listener for the
 * `SalesOrderReturned` event.
 *
 * Notifies admins plus staff at the returning order's warehouse.
 */
class NotifySalesOrderReturned implements ShouldQueue
{
    public function handle(SalesOrderReturned $event): void
    {
        $order = SalesOrder::find($event->salesOrderId);
        if (! $order) {
            return;
        }

        $users = User::query()
            ->where(function ($q) use ($order) {
                $q->where('role', UserRole::Admin->value)
                    ->orWhereHas('warehouses', function ($query) use ($order) {
                        $query->whereIn('warehouses.id', [$order->warehouse_id]);
                    });
            })
            ->get();

        Notification::send($users, new SalesOrderReturnedNotification(
            $order->id,
            $order->reference_code,
        ));
    }
}
