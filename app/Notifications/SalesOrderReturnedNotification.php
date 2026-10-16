<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * §22.3b database notification for the `SalesOrderReturned` event.
 *
 * Translation key: `notifications.sales_order_returned.body` — MUST be
 * present in every `lang/{locale}/notifications.php` (§0A.15 parity).
 */
class SalesOrderReturnedNotification extends Notification
{
    public function __construct(
        public readonly int $salesOrderId,
        public readonly ?string $referenceCode = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'sales_order_id' => $this->salesOrderId,
            'reference_code' => $this->referenceCode,
            'message' => __('notifications.sales_order_returned.body', [
                'reference' => $this->referenceCode ?? (string) $this->salesOrderId,
            ]),
        ];
    }
}
