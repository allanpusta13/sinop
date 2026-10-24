<?php

declare(strict_types=1);

return [
    'transfer_confirmed' => [
        'body' => 'La transferencia :reference ha sido confirmada.',
    ],
    'transfer_cancelled' => [
        'body' => 'La transferencia :reference ha sido cancelada.',
    ],
    'transfer_dispatched' => [
        'body' => 'La transferencia :reference ha sido despachada.',
    ],
    'transfer_received' => [
        'body' => 'La transferencia :reference ha sido recibida.',
    ],
    'loss_recorded' => [
        'body' => 'Se registró una pérdida de :qty unidades base para :sku.',
    ],
    'purchase_order_received' => [
        'body' => 'La orden de compra :reference ha sido recibida.',
    ],
    'purchase_order_cancelled' => [
        'body' => 'La orden de compra :reference ha sido cancelada.',
    ],
    'sales_order_dispatched' => [
        'body' => 'La orden de venta :reference ha sido despachada.',
    ],
    'sales_order_cancelled' => [
        'body' => 'La orden de venta :reference ha sido cancelada.',
    ],
    'sales_order_returned' => [
        'body' => 'La orden de venta :reference tiene una devolución registrada.',
    ],
    'inventory_below_reorder_point' => [
        'body' => ':sku está por debajo de su punto de reorden en :warehouse.',
    ],
];
