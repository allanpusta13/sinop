<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a sales order transitions to Cancelled (§6.5
 * cancelSalesOrder).
 *
 * §22.1 lists this event in the catalogue; §6.5 fires it.
 */
class SalesOrderCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly int $salesOrderId) {}
}
