<?php

declare(strict_types=1);

return [
    'transfer_confirmed' => [
        'body' => 'Transfer :reference has been confirmed.', // TODO(i18n): translate
    ],
    'transfer_cancelled' => [
        'body' => 'Transfer :reference has been cancelled.', // TODO(i18n): translate
    ],
    'transfer_dispatched' => [
        'body' => 'Transfer :reference has been dispatched.', // TODO(i18n): translate
    ],
    'transfer_received' => [
        'body' => 'Transfer :reference has been received.', // TODO(i18n): translate
    ],
    'loss_recorded' => [
        'body' => 'Loss of :qty base units recorded for :sku.', // TODO(i18n): translate
    ],
    'purchase_order_received' => [
        'body' => 'Purchase order :reference has been received.', // TODO(i18n): translate
    ],
    'purchase_order_cancelled' => [
        'body' => 'Purchase order :reference has been cancelled.', // TODO(i18n): translate
    ],
    'sales_order_dispatched' => [
        'body' => 'Sales order :reference has been dispatched.', // TODO(i18n): translate
    ],
    'sales_order_cancelled' => [
        'body' => 'La orden de venta :reference ha sido cancelada.',
    ],
    'sales_order_returned' => [
        'body' => 'La orden de venta :reference tiene una devolución registrada.',
    ],
    'inventory_below_reorder_point' => [
        'body' => ':sku is below its reorder point in :warehouse.', // TODO(i18n): translate
    ],
];
