<?php

declare(strict_types=1);

/**
 * Export suite translations (#225 / #226).
 *
 * Generic action strings live under `action` — one Export action per
 * List page, so per-resource labels would only duplicate each other.
 * Type labels (`types.*`) mirror the 19 registry keys in
 * config/exports.php; structural column labels (`columns.*`) cover the
 * ids and reference fields that no resource field key describes.
 * Feature column headers reuse `resources.*` keys so a file's headers
 * match what the user sees in the table.
 *
 * Notification and error bodies take bare `:param` keys — Laravel
 * prefixes the colon itself when the value is passed in code.
 */
return [

    'action' => [
        'label' => 'Export',
        'heading' => 'Export data',
        'description' => 'Choose what to export and the file format. The export follows the current table filters and your warehouse access.',
        'submit_label' => 'Export',
        'type_label' => 'Data to export',
        'format_label' => 'File format',
    ],

    'notifications' => [
        'completed_title' => 'Export ready',
        'completed_body' => ':type export finished — :rows rows. Download the file to keep a copy.',
        'queued_title' => 'Export queued',
        'queued_body' => ':type export (:rows rows) is being generated. You will get a notification with the download link when it is ready.',
        'download_label' => 'Download file',
        'failed_title' => 'Export failed',
        'failed_body' => 'The export could not be generated. Please try again.',
        'scope_lost_title' => 'Export cancelled',
        'scope_lost_body' => 'Your warehouse access changed while the :type export was being generated, and no warehouses are left for it to read from. No file was produced.',
    ],

    'errors' => [
        'unknown_definition' => 'Unknown export type: :key',
        'unsupported_format' => 'Unsupported export format.',
        'invalid_filename' => 'Invalid export filename.',
    ],

    'types' => [
        'products' => [
            'families' => 'Product families',
            'variants' => 'Product variants',
            'current_prices' => 'Current prices',
            'unit_conversions' => 'Unit conversions',
        ],
        'warehouses' => 'Warehouses',
        'suppliers' => 'Suppliers',
        'customers' => 'Customers',
        'stock_movements' => 'Stock movements',
        'loss_ledgers' => 'Loss ledger entries',
        'transfer_requisitions' => [
            'label' => 'Transfer requisitions',
            'items' => 'Transfer requisition items',
            'revisions' => 'Transfer requisition revisions',
        ],
        'in_transits' => 'In-transit records',
        'purchase_orders' => [
            'label' => 'Purchase orders',
            'items' => 'Purchase order items',
        ],
        'sales_orders' => [
            'label' => 'Sales orders',
            'items' => 'Sales order items',
        ],
        'direct_transfers' => [
            'label' => 'Direct transfers',
            'items' => 'Direct transfer items',
        ],
    ],

    'columns' => [
        'effective_from' => 'Effective from',
        'movement_id' => 'Movement ID',
        'loss_id' => 'Loss ID',
        'revision_id' => 'Revision ID',
        'item_id' => 'Item ID',
        'transfer_requisition_id' => 'Transfer requisition ID',
        'transfer_requisition_item_id' => 'Transfer requisition item ID',
        'purchase_order_id' => 'Purchase order ID',
        'sales_order_id' => 'Sales order ID',
        'direct_transfer_id' => 'Direct transfer ID',
        'product_variant_id' => 'Product variant ID',
        'substitute_product_variant_id' => 'Substitute product variant ID',
        'warehouse_id' => 'Warehouse ID',
        'from_warehouse_id' => 'From warehouse ID',
        'to_warehouse_id' => 'To warehouse ID',
        'supplier_id' => 'Supplier ID',
        'customer_id' => 'Customer ID',
        'unit_ratio_used' => 'Unit ratio used',
        'related_movement_id' => 'Related movement ID',
        'reference_type' => 'Reference type',
        'reference_id' => 'Reference ID',
        'requested_unit' => 'Requested unit',
        'approved_unit' => 'Approved unit',
        'proposed_unit' => 'Proposed unit',
    ],

];
