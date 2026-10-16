<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a sales return is recorded against an item (§6.5
 * recordSalesReturn). The payload carries the parent sales-order id so
 * the listener can resolve the order and its warehouse.
 *
 * §22.1 lists this event in the catalogue; §6.5 fires it.
 */
class SalesOrderReturned implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly int $salesOrderId) {}
}
