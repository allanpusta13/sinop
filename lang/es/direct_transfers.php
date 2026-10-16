<?php

declare(strict_types=1);

return [
    'model' => [
        'singular' => 'Direct Transfer', // TODO(i18n): translate
        'plural' => 'Direct Transfers', // TODO(i18n): translate
    ],
    'page' => [
        'create_heading' => 'Create Direct Transfer', // TODO(i18n): translate
        'create_description' => 'Execute a multi-line direct transfer between warehouses', // TODO(i18n): translate
        'execute_transfer' => 'Execute Transfer', // TODO(i18n): translate
    ],
    'form' => [
        'step1_title' => 'Location Mapping', // TODO(i18n): translate
        'step1_desc' => 'Select the origin and destination warehouses for this transfer.', // TODO(i18n): translate
        'step2_title' => 'Stock Allocation', // TODO(i18n): translate
        'step2_desc' => 'Add product variants and quantities to transfer.', // TODO(i18n): translate
        'step3_title' => 'Review & Verify', // TODO(i18n): translate
        'step3_desc' => 'Review the complete transfer summary before execution.', // TODO(i18n): translate
        'sections' => [
            'routing' => 'Warehouse Routing', // TODO(i18n): translate
        ],
        'fields' => [
            'origin_warehouse' => 'Origin Warehouse', // TODO(i18n): translate
            'destination_warehouse' => 'Destination Warehouse', // TODO(i18n): translate
            'items' => 'Items', // TODO(i18n): translate
            'product_variant' => 'Product Variant', // TODO(i18n): translate
            'unit_name' => 'Unit', // TODO(i18n): translate
            'unit_ratio' => 'Ratio (to Base)', // TODO(i18n): translate
            'qty' => 'Quantity', // TODO(i18n): translate
            'base_qty' => 'Base Quantity (Computed)', // TODO(i18n): translate
            'line_notes' => 'Line Notes', // TODO(i18n): translate
            'line_notes_placeholder' => 'Optional notes for this line', // TODO(i18n): translate
            'transfer_notes' => 'Transfer Notes', // TODO(i18n): translate
            'transfer_notes_placeholder' => 'Notes for the entire transfer', // TODO(i18n): translate
        ],
        'hints' => [
            'ratio_auto' => 'Auto-filled based on selected unit', // TODO(i18n): translate
        ],
        'add_line' => 'Add Line', // TODO(i18n): translate
        'review_verify' => 'Review & Verify', // TODO(i18n): translate
        'complete_previous_steps' => 'Complete the previous steps to see a review summary.', // TODO(i18n): translate
        'origin' => 'Origin', // TODO(i18n): translate
        'destination' => 'Destination', // TODO(i18n): translate
        'sku' => 'SKU', // TODO(i18n): translate
        'variant' => 'Variant', // TODO(i18n): translate
        'qty_unit' => 'Qty / Unit', // TODO(i18n): translate
        'base_units' => 'Base Units', // TODO(i18n): translate
        'notes' => 'Notes', // TODO(i18n): translate
        'errors' => [
            'same_warehouse' => 'Origin and destination warehouses cannot be the same.', // TODO(i18n): translate
            'no_items' => 'At least one transfer line is required.', // TODO(i18n): translate
            'missing_fields' => 'All fields are required for each transfer line.', // TODO(i18n): translate
            'invalid_unit_ratio' => 'Unit ratio must be at least 1.', // TODO(i18n): translate
            'invalid_qty' => 'Quantity must be at least 1.', // TODO(i18n): translate
            'invalid_base_qty' => 'Base quantity must be at least 1.', // TODO(i18n): translate
        ],
    ],
    'table' => [
        'reference_code' => 'Reference', // TODO(i18n): translate
        'reference' => 'Reference', // TODO(i18n): translate
        'from_warehouse' => 'From', // TODO(i18n): translate
        'to_warehouse' => 'To', // TODO(i18n): translate
        'transferred_by' => 'Transferred By', // TODO(i18n): translate
        'items_count' => 'Lines', // TODO(i18n): translate
        'items' => 'Lines', // TODO(i18n): translate
        'transferred_at' => 'Transferred At', // TODO(i18n): translate
        'filter_from_warehouse' => 'From Warehouse', // TODO(i18n): translate
        'filter_to_warehouse' => 'To Warehouse', // TODO(i18n): translate
    ],
    'notifications' => [
        'transfer_executed' => 'Direct Transfer Executed', // TODO(i18n): translate
        'transfer_completed' => 'Direct transfer :code has been completed successfully.', // TODO(i18n): translate
    ],
    'actions' => [
        'view' => 'View', // TODO(i18n): translate
    ],
    'fields' => [
        'reference_code' => 'Reference Code', // TODO(i18n): translate
        'transferred_at' => 'Transferred At', // TODO(i18n): translate
        'from_warehouse' => 'Origin Warehouse', // TODO(i18n): translate
        'to_warehouse' => 'Destination Warehouse', // TODO(i18n): translate
        'transferred_by' => 'Transferred By', // TODO(i18n): translate
        'sku' => 'SKU', // TODO(i18n): translate
        'qty' => 'Quantity', // TODO(i18n): translate
        'ratio' => 'Ratio', // TODO(i18n): translate
        'base_qty' => 'Base Quantity', // TODO(i18n): translate
    ],
];
