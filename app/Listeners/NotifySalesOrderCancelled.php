<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\SalesOrderCancelled;
use App\Models\SalesOrder;
use App\Models\User;
use App\Notifications\SalesOrderCancelledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * NotifySalesOrderCancelled — §22.3a listener for the
 * `SalesOrderCancelled` event.
 *
 * Notifies admins plus staff at the cancelling warehouse.
 */
class NotifySalesOrderCancelled implements ShouldQueue
{
    public function handle(SalesOrderCancelled $event): void
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

        Notification::send($users, new SalesOrderCancelledNotification(
            $order->id,
            $order->reference_code,
        ));
    }
}
