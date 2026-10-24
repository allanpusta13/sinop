<?php

declare(strict_types=1);

return [
    'transfer_confirmed' => [
        'body' => 'Nakumpirma na ang transfer :reference.',
    ],
    'transfer_cancelled' => [
        'body' => 'Kinansela na ang transfer :reference.',
    ],
    'transfer_dispatched' => [
        'body' => 'Naipadala na ang transfer :reference.',
    ],
    'transfer_received' => [
        'body' => 'Natanggap na ang transfer :reference.',
    ],
    'loss_recorded' => [
        'body' => 'May naitalang pagkawala na :qty base units para sa :sku.',
    ],
    'purchase_order_received' => [
        'body' => 'Natanggap na ang purchase order :reference.',
    ],
    'purchase_order_cancelled' => [
        'body' => 'Kinansela na ang purchase order :reference.',
    ],
    'sales_order_dispatched' => [
        'body' => 'Naipadala na ang sales order :reference.',
    ],
    'sales_order_cancelled' => [
        'body' => 'Kinansela ang sales order :reference.',
    ],
    'sales_order_returned' => [
        'body' => 'May naitalang return ang sales order :reference.',
    ],
    'inventory_below_reorder_point' => [
        'body' => 'Nasa ibaba na ng reorder point nito ang :sku sa :warehouse.',
    ],
];
