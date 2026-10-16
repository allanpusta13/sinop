<?php

declare(strict_types=1);

return [
    'stats' => [
        'on_hand' => 'On hand', // TODO(i18n): translate
        'in_transit' => 'In transit', // TODO(i18n): translate
        'pending' => 'Pending', // TODO(i18n): translate
        'write_off' => 'Write-off', // TODO(i18n): translate
        'warehouse' => 'Warehouse', // TODO(i18n): translate
        'on_hand_description' => 'Total base units across in-scope warehouses.', // TODO(i18n): translate
        'pending_description' => 'Requisitions awaiting dispatch or confirmation.', // TODO(i18n): translate
        'in_transit_description' => 'Dispatched cargo not yet accounted at destination.', // TODO(i18n): translate
        'write_off_description' => 'Total financial loss recorded.', // TODO(i18n): translate
    ],
    'charts' => [
        'available' => 'Available', // TODO(i18n): translate
        'dispatched' => 'Dispatched', // TODO(i18n): translate
        'net_movement' => 'Net movement', // TODO(i18n): translate
        'purchases' => 'Purchases', // TODO(i18n): translate
        'revenue' => 'Revenue', // TODO(i18n): translate
        'sales' => 'Sales', // TODO(i18n): translate
    ],
    'pending' => [
        'purchases' => 'Pending purchases', // TODO(i18n): translate
        'sales' => 'Pending sales', // TODO(i18n): translate
        'warehouse' => 'Warehouse', // TODO(i18n): translate
    ],
    'quick_actions' => [
        'new_transfer' => 'New transfer', // TODO(i18n): translate
        'new_direct_transfer' => 'New direct transfer', // TODO(i18n): translate
        'new_purchase' => 'New purchase', // TODO(i18n): translate
        'new_sale' => 'New sale', // TODO(i18n): translate
    ],
    'active_in_transit' => [
        'qty' => 'Qty', // TODO(i18n): translate
        'requisition' => 'Requisition', // TODO(i18n): translate
        'sku' => 'SKU', // TODO(i18n): translate
        'status' => 'Status', // TODO(i18n): translate
    ],
];
