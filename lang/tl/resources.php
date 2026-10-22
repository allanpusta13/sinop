<?php

declare(strict_types=1);

return [
    'products' => [
        'model' => [
            'singular' => 'Product',
            'plural' => 'Products',
        ],
        'navigation' => [
            'label' => 'Products', // TODO(i18n): translate
        ],
        'sections' => [
            'inventory' => 'Inventory',
        ],
        'tabs' => [
            'identity' => 'Identity', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
            'stock_pricing' => 'Stock & Pricing', // TODO(i18n): translate
        ],
        'wizard' => [
            'review' => 'Review',
        ],
        'fields' => [
            'sku' => 'SKU',
            'family_name' => 'Family name', // TODO(i18n): translate
            'family_category' => 'Category', // TODO(i18n): translate
            'variant_name' => 'Variant name', // TODO(i18n): translate
            'barcode' => 'Barcode',
            'base_unit' => 'Base Unit',
            'base_unit_name' => 'Base unit', // TODO(i18n): translate
            'base_unit_ratio' => 'Base unit ratio', // TODO(i18n): translate
            'unit_name' => 'Unit', // TODO(i18n): translate
            'unit_conversions' => 'Unit conversions', // TODO(i18n): translate
            'reorder_point' => 'Reorder Point',
            'attributes' => 'Attributes', // TODO(i18n): translate
            'images' => 'Images', // TODO(i18n): translate
            'is_active' => 'Active', // TODO(i18n): translate
            'is_default_purchase' => 'Default purchase unit', // TODO(i18n): translate
            'is_default_transfer' => 'Default transfer unit', // TODO(i18n): translate
            'cost_price' => 'Cost price', // TODO(i18n): translate
            'sale_price' => 'Sale price', // TODO(i18n): translate
            'price_change_notes' => 'Price change notes', // TODO(i18n): translate
            'product_family' => 'Product family', // TODO(i18n): translate
            'warehouse' => 'Warehouse', // TODO(i18n): translate
            'signed_quantity_base' => 'Signed quantity (base units)', // TODO(i18n): translate
            'adjustment_notes' => 'Adjustment notes', // TODO(i18n): translate
        ],
        'hints' => [
            'signed_quantity' => 'Positive adds stock, negative removes stock.', // TODO(i18n): translate
        ],
        'filters' => [
            'is_active' => 'Active', // TODO(i18n): translate
            'product_family' => 'Product family', // TODO(i18n): translate
        ],
        'table' => [
            'sku' => 'SKU', // TODO(i18n): translate
            'name' => 'Name', // TODO(i18n): translate
            'family' => 'Family', // TODO(i18n): translate
            'base_unit' => 'Base unit', // TODO(i18n): translate
            'reorder_point' => 'Reorder point', // TODO(i18n): translate
            'sale_price' => 'Sale price', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
            'empty_heading' => 'No products found', // TODO(i18n): translate
            'empty_description' => 'Create your first product to get started.', // TODO(i18n): translate
        ],
        'infolist' => [
            'identity' => 'Identity', // TODO(i18n): translate
            'pricing' => 'Pricing', // TODO(i18n): translate
            'unit_conversions' => 'Unit conversions', // TODO(i18n): translate
        ],
        'actions' => [
            'create' => 'New product', // TODO(i18n): translate
            'edit' => 'Edit', // TODO(i18n): translate
            'view' => 'View', // TODO(i18n): translate
            'delete' => 'Delete', // TODO(i18n): translate
            'restore' => 'Restore', // TODO(i18n): translate
            'force_delete' => 'Delete permanently', // TODO(i18n): translate
            'set_current_price' => 'Set current price', // TODO(i18n): translate
            'set_current_price_heading' => 'Set current price', // TODO(i18n): translate
            'set_current_price_description' => 'Replace the current price for this variant.', // TODO(i18n): translate
            'manage_units' => 'Manage Units',
            'manage_units_heading' => 'Manage units', // TODO(i18n): translate
            'manage_units_description' => 'Edit unit conversions for this variant.', // TODO(i18n): translate
            'edit_family' => 'Edit family', // TODO(i18n): translate
            'edit_family_heading' => 'Edit family', // TODO(i18n): translate
            'edit_family_description' => 'Edit the parent product family.', // TODO(i18n): translate
            'quick_adjustment' => 'Quick Adjustment',
            'quick_adjustment_heading' => 'Quick stock adjustment', // TODO(i18n): translate
            'quick_adjustment_description' => 'Record a manual stock adjustment.', // TODO(i18n): translate
            'more_actions' => 'More actions', // TODO(i18n): translate
            'import' => 'Import', // TODO(i18n): translate
            'import_heading' => 'Import Products and Families', // TODO(i18n): translate
            'import_description' => 'Controlled import for product families and variants with preview and verification.', // TODO(i18n): translate
        ],
        'notifications' => [
            'created' => 'Nalikha ng matagumpay ang product.',
            'updated' => 'Na-update ng matagumpay ang product.',
            'price_updated' => 'Price updated.', // TODO(i18n): translate
            'adjustment_recorded' => 'Adjustment recorded.', // TODO(i18n): translate
            'family_updated' => 'Family updated.', // TODO(i18n): translate
            'family_missing' => 'Product family is missing.', // TODO(i18n): translate
        ],
        'import' => [
            'mode_families' => 'Product Families', // TODO(i18n): translate
            'mode_families_desc' => 'Import parent product family groups (Name and optional Category).', // TODO(i18n): translate
            'mode_variants' => 'Product Variants', // TODO(i18n): translate
            'mode_variants_desc' => 'Import SKU-bearing product variants associated with existing families.', // TODO(i18n): translate
            'map_family_id' => 'Family ID (optional)', // TODO(i18n): translate
            'map_family_name' => 'Family Name', // TODO(i18n): translate
            'map_family_category' => 'Family Category (optional)', // TODO(i18n): translate
            'map_sku' => 'SKU', // TODO(i18n): translate
            'map_variant_name' => 'Variant Name', // TODO(i18n): translate
            'map_base_unit' => 'Base Unit Name', // TODO(i18n): translate
            'map_barcode' => 'Barcode (optional)', // TODO(i18n): translate
            'map_reorder_point' => 'Reorder Point (optional)', // TODO(i18n): translate
            'map_attributes' => 'Attributes (optional)', // TODO(i18n): translate
            'map_is_active' => 'Active Status (optional)', // TODO(i18n): translate
            'desc_family_id' => 'Optional explicit family identifier. Leave blank to resolve the family by name.', // TODO(i18n): translate
            'desc_family_name' => 'The existing family name to resolve, or the name to create on family imports. Family imports never update by name.', // TODO(i18n): translate
            'desc_family_category' => 'Optional category for the family. Blank keeps the existing or default value.', // TODO(i18n): translate
            'desc_sku' => 'The variant SKU (unique). Existing variants match by SKU and update only after confirmation.', // TODO(i18n): translate
            'desc_variant_name' => 'The display name of the variant.', // TODO(i18n): translate
            'desc_base_unit' => 'The variant base unit name, e.g. Bag, Box, Kg.', // TODO(i18n): translate
            'desc_barcode' => 'Optional EAN-13 barcode. Must be unique across variants when present.', // TODO(i18n): translate
            'desc_reorder_point' => 'Optional non-negative integer reorder point.', // TODO(i18n): translate
            'desc_attributes' => 'Optional JSON object of variant attributes, e.g. {"Roast":"Medium"}.', // TODO(i18n): translate
            'desc_is_active' => 'Optional active flag: 1 or 0. Blank defaults to active (1).', // TODO(i18n): translate
            'errors' => [
                'family_name_required' => 'Family name is required.', // TODO(i18n): translate
                'family_duplicate_in_file' => 'Duplicate family name ":name" in the uploaded file.', // TODO(i18n): translate
                'family_name_exists' => 'A product family named ":name" already exists. Automatic update by name is not permitted; review the existing family.', // TODO(i18n): translate
                'family_id_not_found' => 'Family ID ":family_id" does not identify an existing family.', // TODO(i18n): translate
                'family_name_no_match' => 'Family name ":name" does not match any existing product family.', // TODO(i18n): translate
                'family_name_ambiguous' => 'Family name ":name" is ambiguous; matches :count product families.', // TODO(i18n): translate
                'family_reference_missing' => 'Variant row must provide an explicit family ID or family name.', // TODO(i18n): translate
                'sku_required' => 'SKU is required.', // TODO(i18n): translate
                'variant_name_required' => 'Variant name is required.', // TODO(i18n): translate
                'base_unit_required' => 'Base unit name is required.', // TODO(i18n): translate
                'sku_duplicate_in_file' => 'Duplicate SKU ":sku" in the uploaded file. Rejected for review.', // TODO(i18n): translate
                'barcode_multiple_in_file' => 'Barcode ":barcode" is used by multiple different SKUs in the uploaded file.', // TODO(i18n): translate
                'barcode_conflict' => 'Barcode ":barcode" is already associated with a different SKU (:sku).', // TODO(i18n): translate
                'reorder_point_invalid' => 'Reorder point must be a non-negative integer.', // TODO(i18n): translate
                'attributes_invalid_json' => 'Attributes must be valid JSON.', // TODO(i18n): translate
                'is_active_invalid' => 'Active status must be a yes or no value.', // TODO(i18n): translate
                'family_identifier_mapping_required' => 'At least one family mapping column is required: Family ID or Family Name.', // TODO(i18n): translate
            ],
        ],
    ],
    'transfer_requisitions' => [
        'model' => [
            'singular' => 'Transfer Requisition',
            'plural' => 'Transfer Requisitions',
        ],
        'navigation' => [
            'label' => 'Transfer Requisitions', // TODO(i18n): translate
        ],
        'badge_tooltip' => 'Requisitions awaiting review', // TODO(i18n): translate
        'sections' => [
            'routing' => 'Routing', // TODO(i18n): translate
            'revision_manifest' => 'Rebisyon',
        ],
        'steps' => [
            'routing' => 'Routing',
            'routing_description' => 'Choose source and destination warehouses.', // TODO(i18n): translate
            'manifest' => 'Manifest', // TODO(i18n): translate
            'manifest_description' => 'Add requested line items.', // TODO(i18n): translate
            'review' => 'Review',
            'review_description' => 'Verify before creating.', // TODO(i18n): translate
        ],
        'fields' => [
            'reference_code' => 'Reference', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
            'from_warehouse' => 'From Warehouse',
            'to_warehouse' => 'To Warehouse',
            'notes' => 'Notes', // TODO(i18n): translate
            'variant_sku' => 'Variant SKU', // TODO(i18n): translate
            'original_sku' => 'Original SKU', // TODO(i18n): translate
            'substitute_sku' => 'Substitute SKU', // TODO(i18n): translate
            'unit' => 'Unit', // TODO(i18n): translate
            'ratio_base' => 'Ratio (base)', // TODO(i18n): translate
            'qty' => 'Qty', // TODO(i18n): translate
            'requested' => 'Requested', // TODO(i18n): translate
            'approved' => 'Approved', // TODO(i18n): translate
            'proposed' => 'Proposed', // TODO(i18n): translate
            'requested_by' => 'Requested by', // TODO(i18n): translate
            'approved_by' => 'Approved by', // TODO(i18n): translate
            'dispatched_by' => 'Dispatched by', // TODO(i18n): translate
            'received_by' => 'Received by', // TODO(i18n): translate
            'shipped_base' => 'Shipped (base)', // TODO(i18n): translate
            'received_good_base' => 'Received good (base)', // TODO(i18n): translate
            'revision' => 'Revision', // TODO(i18n): translate
            'revisions' => 'Revisions', // TODO(i18n): translate
            'revision_item' => 'Revision item', // TODO(i18n): translate
            'negotiation_side' => 'Side', // TODO(i18n): translate
            'negotiation_reason' => 'Negotiation reason', // TODO(i18n): translate
            'negotiation_reason_placeholder' => 'Explain the reason for the proposed change.', // TODO(i18n): translate
            'proposed_by' => 'Proposed by', // TODO(i18n): translate
            'responds_to' => 'Responds to', // TODO(i18n): translate
            'responded_at' => 'Responded at', // TODO(i18n): translate
        ],
        'hints' => [
            'ratio_auto' => 'Ratio is resolved automatically from the unit.', // TODO(i18n): translate
        ],
        'filters' => [
            'from_warehouse' => 'From warehouse', // TODO(i18n): translate
            'to_warehouse' => 'To warehouse', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
        ],
        'table' => [
            'reference' => 'Reference', // TODO(i18n): translate
            'from' => 'From', // TODO(i18n): translate
            'to' => 'To', // TODO(i18n): translate
            'items' => 'Items', // TODO(i18n): translate
            'requested_by' => 'Requested by', // TODO(i18n): translate
            'requested' => 'Requested', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
            'empty_heading' => 'No transfer requisitions found', // TODO(i18n): translate
            'empty_description' => 'Create your first requisition to get started.', // TODO(i18n): translate
        ],
        'infolist' => [
            'profile' => 'Profile', // TODO(i18n): translate
            'signoffs' => 'Sign-offs', // TODO(i18n): translate
            'manifest' => 'Manifest', // TODO(i18n): translate
            'negotiation_history' => 'Negotiation history', // TODO(i18n): translate
        ],
        'placeholders' => [
            'system_initialized' => 'System',
            'pending_approval' => 'Naghihintay ng approval',
            'pending_dispatch' => 'Naghihintay ng dispatch',
            'pending_intake' => 'Naghihintay ng intake',
            'not_yet_responded' => 'Wala pang sagot',
        ],
        'actions' => [
            'create' => 'New transfer requisition', // TODO(i18n): translate
            'edit' => 'Edit', // TODO(i18n): translate
            'view' => 'View', // TODO(i18n): translate
            'delete' => 'Delete', // TODO(i18n): translate
            'restore' => 'Restore', // TODO(i18n): translate
            'force_delete' => 'Delete permanently', // TODO(i18n): translate
            'submit' => 'Submit request', // TODO(i18n): translate
            'submit_heading' => 'Submit request', // TODO(i18n): translate
            'submit_description' => 'Send this requisition for review.', // TODO(i18n): translate
            'review' => 'Review / negotiate', // TODO(i18n): translate
            'propose_revision' => 'Propose revision', // TODO(i18n): translate
            'propose_revision_heading' => 'Propose revision', // TODO(i18n): translate
            'propose_revision_description' => 'Propose a substitute variant or quantity.', // TODO(i18n): translate
            'accept_revision' => 'Accept revision', // TODO(i18n): translate
            'accept_revision_heading' => 'Accept revision', // TODO(i18n): translate
            'accept_revision_description' => 'Accept the selected pending revision.', // TODO(i18n): translate
            'reject_revision' => 'Reject revision', // TODO(i18n): translate
            'reject_revision_heading' => 'Reject revision', // TODO(i18n): translate
            'reject_revision_description' => 'Reject the selected pending revision.', // TODO(i18n): translate
            'confirm' => 'Confirm',
            'confirm_heading' => 'Confirm requisition', // TODO(i18n): translate
            'confirm_description' => 'Confirm the negotiated manifest.', // TODO(i18n): translate
            'dispatch' => 'Dispatch',
            'dispatch_heading' => 'Dispatch stock', // TODO(i18n): translate
            'dispatch_description' => 'Dispatch approved quantities.', // TODO(i18n): translate
            'receive' => 'Receive',
            'record_loss' => 'Record loss', // TODO(i18n): translate
            'record_loss_heading' => 'Record loss', // TODO(i18n): translate
            'record_loss_description' => 'Record lost or damaged quantities.', // TODO(i18n): translate
            'cancel' => 'Cancel', // TODO(i18n): translate
            'cancel_heading' => 'Cancel requisition', // TODO(i18n): translate
            'cancel_description' => 'Cancel this requisition.', // TODO(i18n): translate
            'more_actions' => 'More actions', // TODO(i18n): translate
        ],
        'notifications' => [
            'dispatched' => 'Ipinadala ang transfer requisition.',
            'loss_recorded' => 'Loss recorded.', // TODO(i18n): translate
            'revision_submitted' => 'Revision submitted.', // TODO(i18n): translate
            'revision_accepted' => 'Revision accepted.', // TODO(i18n): translate
            'revision_rejected' => 'Revision rejected.', // TODO(i18n): translate
        ],
    ],
    'direct_transfers' => [
        'model' => [
            'singular' => 'Direct Transfer',
            'plural' => 'Direct Transfers',
        ],
        'navigation' => [
            'label' => 'Direct Transfers', // TODO(i18n): translate
        ],
        'sections' => [
            'routing' => 'Routing', // TODO(i18n): translate
            'stock_allocation' => 'Stock allocation', // TODO(i18n): translate
        ],
        'steps' => [
            'location_mapping' => 'Locations', // TODO(i18n): translate
            'location_mapping_description' => 'Choose source and destination.', // TODO(i18n): translate
            'stock_allocation' => 'Stock allocation', // TODO(i18n): translate
            'stock_allocation_description' => 'Allocate line quantities.', // TODO(i18n): translate
            'review_verify' => 'Review', // TODO(i18n): translate
            'review_verify_description' => 'Verify before transferring.', // TODO(i18n): translate
        ],
        'fields' => [
            'from_warehouse' => 'Origin Warehouse',
            'to_warehouse' => 'Destination Warehouse',
            'variant_sku' => 'Variant SKU', // TODO(i18n): translate
            'sku' => 'SKU',
            'unit' => 'Unit', // TODO(i18n): translate
            'ratio' => 'Ratio',
            'ratio_base' => 'Ratio (base)', // TODO(i18n): translate
            'qty' => 'Quantity',
            'base_qty' => 'Base Quantity',
            'notes' => 'Notes', // TODO(i18n): translate
            'reference_code' => 'Reference Code',
            'transferred_by' => 'Transferred By',
            'transferred_at' => 'Transferred At',
        ],
        'hints' => [
            'ratio_auto' => 'Ratio is resolved automatically from the unit.', // TODO(i18n): translate
        ],
        'filters' => [
            'from_warehouse' => 'From warehouse', // TODO(i18n): translate
            'to_warehouse' => 'To warehouse', // TODO(i18n): translate
        ],
        'table' => [
            'reference' => 'Reference',
            'from' => 'From', // TODO(i18n): translate
            'to' => 'To', // TODO(i18n): translate
            'items' => 'Lines',
            'by' => 'By', // TODO(i18n): translate
            'transferred_at' => 'Transferred At',
            'empty_heading' => 'No direct transfers found', // TODO(i18n): translate
            'empty_description' => 'Create your first direct transfer to get started.', // TODO(i18n): translate
        ],
        'infolist' => [
            'profile' => 'Profile', // TODO(i18n): translate
            'manifest' => 'Manifest', // TODO(i18n): translate
            'authorization' => 'Authorization', // TODO(i18n): translate
        ],
        'actions' => [
            'create' => 'New direct transfer', // TODO(i18n): translate
            'view' => 'View',
            'delete' => 'Delete', // TODO(i18n): translate
            'force_delete' => 'Delete permanently', // TODO(i18n): translate
        ],
    ],
    'in_transits' => [
        'model' => [
            'singular' => 'In-Transit',
            'plural' => 'In-Transits',
        ],
        'navigation' => [
            'label' => 'In-Transit Cargo', // TODO(i18n): translate
        ],
        'badge_tooltip' => 'Cargo currently in transit', // TODO(i18n): translate
        'fields' => [
            'requisition' => 'Requisition',
            'sku' => 'SKU',
            'status' => 'Status',
            'dispatched_base' => 'Dispatched Base Qty',
            'dispatched_at' => 'Dispatched At',
            'cleared_at' => 'Cleared At',
        ],
        'filters' => [
            'status' => 'Status', // TODO(i18n): translate
        ],
        'table' => [
            'requisition' => 'Requisition', // TODO(i18n): translate
            'sku' => 'SKU', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
            'dispatched' => 'Dispatched', // TODO(i18n): translate
            'dispatched_at' => 'Dispatched at', // TODO(i18n): translate
            'empty_heading' => 'No in-transit cargo', // TODO(i18n): translate
            'empty_description' => 'All dispatched cargo has been accounted for.', // TODO(i18n): translate
        ],
        'infolist' => [
            'cargo' => 'Cargo', // TODO(i18n): translate
        ],
        'actions' => [
            'view' => 'View', // TODO(i18n): translate
        ],
    ],
    'stock_movements' => [
        'model' => [
            'singular' => 'Stock Movement',
            'plural' => 'Stock Movements',
        ],
        'navigation' => [
            'label' => 'Stock Movements', // TODO(i18n): translate
        ],
        'fields' => [
            'sku' => 'SKU', // TODO(i18n): translate
            'warehouse' => 'Warehouse',
            'unit' => 'Unit', // TODO(i18n): translate
            'by' => 'By', // TODO(i18n): translate
            'timestamp' => 'Timestamp', // TODO(i18n): translate
            'reference_code' => 'Reference', // TODO(i18n): translate
            'type' => 'Type',
            'quantity' => 'Quantity',
            'notes' => 'Notes', // TODO(i18n): translate
        ],
        'filters' => [
            'warehouse' => 'Warehouse', // TODO(i18n): translate
            'variant' => 'Variant', // TODO(i18n): translate
            'type' => 'Type', // TODO(i18n): translate
        ],
        'table' => [
            'sku' => 'SKU', // TODO(i18n): translate
            'warehouse' => 'Warehouse', // TODO(i18n): translate
            'type' => 'Type', // TODO(i18n): translate
            'qty' => 'Qty', // TODO(i18n): translate
            'unit' => 'Unit', // TODO(i18n): translate
            'by' => 'By', // TODO(i18n): translate
            'timestamp' => 'Timestamp', // TODO(i18n): translate
            'reference_code' => 'Reference', // TODO(i18n): translate
            'empty_heading' => 'No stock movements found', // TODO(i18n): translate
            'empty_description' => 'Adjust the filters to see more.', // TODO(i18n): translate
        ],
        'infolist' => [
            'movement' => 'Movement', // TODO(i18n): translate
        ],
        'actions' => [
            'view' => 'View', // TODO(i18n): translate
        ],
    ],
    'loss_ledgers' => [
        'model' => [
            'singular' => 'Loss Ledger',
            'plural' => 'Loss Ledgers',
        ],
        'navigation' => [
            'label' => 'Loss Ledgers', // TODO(i18n): translate
        ],
        'fields' => [
            'requisition' => 'Requisition',
            'sku' => 'SKU', // TODO(i18n): translate
            'warehouse' => 'Warehouse', // TODO(i18n): translate
            'lost_base' => 'Lost (base)', // TODO(i18n): translate
            'damaged_base' => 'Damaged (base)', // TODO(i18n): translate
            'notes' => 'Notes', // TODO(i18n): translate
            'transfer_requisition_item' => 'Requisition item', // TODO(i18n): translate
            'recorded_at' => 'Recorded At',
            'loss_category' => 'Loss category', // TODO(i18n): translate
            'unit_cost_price' => 'Unit cost', // TODO(i18n): translate
            'total_financial_loss' => 'Total loss', // TODO(i18n): translate
        ],
        'filters' => [
            'warehouse' => 'Warehouse', // TODO(i18n): translate
            'loss_category' => 'Loss category', // TODO(i18n): translate
        ],
        'table' => [
            'requisition' => 'Requisition', // TODO(i18n): translate
            'sku' => 'SKU', // TODO(i18n): translate
            'warehouse' => 'Warehouse', // TODO(i18n): translate
            'lost' => 'Lost', // TODO(i18n): translate
            'damaged' => 'Damaged', // TODO(i18n): translate
            'category' => 'Category', // TODO(i18n): translate
            'unit_cost' => 'Unit cost', // TODO(i18n): translate
            'total_loss' => 'Total loss', // TODO(i18n): translate
            'by' => 'By', // TODO(i18n): translate
            'recorded' => 'Recorded', // TODO(i18n): translate
            'empty_heading' => 'No losses recorded', // TODO(i18n): translate
            'empty_description' => 'Adjust the filters to see more.', // TODO(i18n): translate
        ],
        'infolist' => [
            'record' => 'Record', // TODO(i18n): translate
            'financial_impact' => 'Financial impact', // TODO(i18n): translate
        ],
        'notes' => [
            'cost_missing' => 'Cost price missing or zero at time of write-off.', // TODO(i18n): translate
        ],
        'actions' => [
            'view' => 'View', // TODO(i18n): translate
        ],
    ],
    'warehouses' => [
        'model' => [
            'singular' => 'Warehouse',
            'plural' => 'Warehouses',
            'singular_desc' => 'Import warehouse records (Code, Name, Location).', // TODO(i18n): translate
        ],
        'navigation' => [
            'label' => 'Warehouses', // TODO(i18n): translate
        ],
        'fields' => [
            'code' => 'Code',
            'name' => 'Name',
            'location' => 'Location',
            'is_active' => 'Active',
            'users' => 'Users', // TODO(i18n): translate
            'assigned_staff' => 'Assigned staff', // TODO(i18n): translate
        ],
        'help' => [
            'code' => 'Leave empty to derive from the name.', // TODO(i18n): translate
            'users_readonly' => 'Assignments are edited from the user record.', // TODO(i18n): translate
        ],
        'placeholders' => [
            'code' => 'Auto-derived when empty', // TODO(i18n): translate
        ],
        'filters' => [
            'is_active' => 'Active', // TODO(i18n): translate
        ],
        'form' => [
            'profile' => 'Profile', // TODO(i18n): translate
            'access_status' => 'Access & status', // TODO(i18n): translate
        ],
        'table' => [
            'code' => 'Code', // TODO(i18n): translate
            'name' => 'Name', // TODO(i18n): translate
            'location' => 'Location', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
            'staff' => 'Staff', // TODO(i18n): translate
            'ledger_entries' => 'Ledger entries', // TODO(i18n): translate
            'empty_heading' => 'No warehouses found', // TODO(i18n): translate
            'empty_description' => 'Create your first warehouse to get started.', // TODO(i18n): translate
        ],
        'infolist' => [
            'profile' => 'Profile', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
            'assigned_staff' => 'Assigned staff', // TODO(i18n): translate
        ],
        'empty_staff' => 'No staff assigned.', // TODO(i18n): translate
        'delete_confirm_description' => 'Delete this warehouse. This action cannot be undone.', // TODO(i18n): translate
        'actions' => [
            'create' => 'New warehouse', // TODO(i18n): translate
            'edit' => 'Edit', // TODO(i18n): translate
            'view' => 'View', // TODO(i18n): translate
            'delete' => 'Delete', // TODO(i18n): translate
            'force_delete' => 'Delete permanently', // TODO(i18n): translate
            'import' => 'Import', // TODO(i18n): translate
            'import_heading' => 'Import warehouses', // TODO(i18n): translate
            'import_description' => 'Controlled import with template download, column mapping, row preview and confirmation.', // TODO(i18n): translate
        ],
        'import' => [
            'desc_code' => 'Unique warehouse code, e.g. WH-0001. Existing warehouses match by code for confirmed updates.', // TODO(i18n): translate
            'desc_name' => 'Warehouse display name.', // TODO(i18n): translate
            'desc_location' => 'Optional free-text location.', // TODO(i18n): translate
            'errors' => [
                'code_required' => 'Warehouse code is required.', // TODO(i18n): translate
                'name_required' => 'Warehouse name is required.', // TODO(i18n): translate
                'duplicate_code_in_file' => 'Duplicate warehouse code ":code" in the uploaded file. Rejected for review.', // TODO(i18n): translate
                'name_conflict' => 'No warehouse with code ":code", but a warehouse named ":name" already exists (:count match(es)). Imports never merge by name — use the existing code to update it.', // TODO(i18n): translate
            ],
        ],
    ],
    'users' => [
        'model' => [
            'singular' => 'User',
            'plural' => 'Users',
        ],
        'navigation' => [
            'label' => 'Users', // TODO(i18n): translate
        ],
        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'Password', // TODO(i18n): translate
            'current_password' => 'Current password', // TODO(i18n): translate
            'new_password' => 'New password', // TODO(i18n): translate
            'password_confirmation' => 'Confirm password', // TODO(i18n): translate
            'role' => 'Role',
            'is_active' => 'Active', // TODO(i18n): translate
            'warehouses' => 'Warehouses',
        ],
        'help' => [
            'warehouses' => 'Warehouses this user may operate in.', // TODO(i18n): translate
            'warehouses_branch_manager' => 'A branch manager needs at least one warehouse, and may manage staff only within them.', // TODO(i18n): translate
            'warehouses_staff' => 'A warehouse staff member is capped at one warehouse and receives stock only.', // TODO(i18n): translate
            'password_leave_blank' => 'Leave blank to keep the current password.', // TODO(i18n): translate
        ],
        'filters' => [
            'warehouse' => 'Warehouse', // TODO(i18n): translate
            'role' => 'Role', // TODO(i18n): translate
            'is_active' => 'Active', // TODO(i18n): translate
        ],
        'table' => [
            'name' => 'Name', // TODO(i18n): translate
            'email' => 'Email', // TODO(i18n): translate
            'role' => 'Role', // TODO(i18n): translate
            'active' => 'Active', // TODO(i18n): translate
            'warehouses' => 'Warehouses', // TODO(i18n): translate
            'created' => 'Created', // TODO(i18n): translate
            'empty_heading' => 'No users found', // TODO(i18n): translate
            'empty_description' => 'Invite your first team member to get started.', // TODO(i18n): translate
        ],
        'actions' => [
            'create' => 'New user', // TODO(i18n): translate
            'edit' => 'Edit', // TODO(i18n): translate
            'delete' => 'Delete', // TODO(i18n): translate
            'edit_profile' => 'Edit profile', // TODO(i18n): translate
            'logout' => 'Sign out', // TODO(i18n): translate
            'reassign_warehouses' => 'Reassign warehouses', // TODO(i18n): translate
        ],
    ],
    'purchase_orders' => [
        'model' => [
            'singular' => 'Purchase Order',
            'plural' => 'Purchase Orders',
        ],
        'navigation' => [
            'label' => 'Purchase Orders', // TODO(i18n): translate
        ],
        'badge_tooltip' => 'Orders awaiting receipt', // TODO(i18n): translate
        'form' => [
            'supplier_warehouse' => 'Supplier & warehouse', // TODO(i18n): translate
        ],
        'steps' => [
            'supplier_warehouse' => 'Supplier & Warehouse',
            'supplier_warehouse_description' => 'Choose supplier and receiving warehouse.', // TODO(i18n): translate
            'line_items' => 'Line Items',
            'line_items_description' => 'Add ordered line items.', // TODO(i18n): translate
            'review_verify' => 'Review & Notes',
            'review_verify_description' => 'Verify before creating.', // TODO(i18n): translate
        ],
        'fields' => [
            'supplier' => 'Supplier',
            'receiving_warehouse' => 'Receiving warehouse', // TODO(i18n): translate
            'variant_sku' => 'Variant SKU', // TODO(i18n): translate
            'sku' => 'SKU', // TODO(i18n): translate
            'product' => 'Product', // TODO(i18n): translate
            'unit' => 'Unit', // TODO(i18n): translate
            'ratio_base' => 'Ratio (base)', // TODO(i18n): translate
            'qty' => 'Qty', // TODO(i18n): translate
            'ordered_base' => 'Ordered (base)', // TODO(i18n): translate
            'received_base' => 'Received (base)', // TODO(i18n): translate
            'unit_cost' => 'Unit cost', // TODO(i18n): translate
            'update_cost_price' => 'Update cost price', // TODO(i18n): translate
            'notes' => 'Notes', // TODO(i18n): translate
            'reference_code' => 'Reference', // TODO(i18n): translate
            'ordered_by' => 'Ordered by', // TODO(i18n): translate
            'received_by' => 'Received by', // TODO(i18n): translate
            'ordered_at' => 'Ordered at', // TODO(i18n): translate
            'received_at' => 'Received at', // TODO(i18n): translate
            'receive_line' => ':sku — outstanding :outstanding :unit (:base base)', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
        ],
        'hints' => [
            'ratio_auto' => 'Ratio is resolved automatically from the unit.', // TODO(i18n): translate
        ],
        'help' => [
            'update_cost_price' => 'Update the variant cost price on receipt.', // TODO(i18n): translate
            'receive_display_equivalent' => ':display :unit', // TODO(i18n): translate
        ],
        'filters' => [
            'supplier' => 'Supplier', // TODO(i18n): translate
            'warehouse' => 'Warehouse', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
        ],
        'table' => [
            'reference' => 'Reference', // TODO(i18n): translate
            'supplier' => 'Supplier', // TODO(i18n): translate
            'warehouse' => 'Warehouse', // TODO(i18n): translate
            'items' => 'Items', // TODO(i18n): translate
            'ordered' => 'Ordered', // TODO(i18n): translate
            'received' => 'Received', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
            'empty_heading' => 'No purchase orders found', // TODO(i18n): translate
            'empty_description' => 'Create your first purchase order to get started.', // TODO(i18n): translate
        ],
        'infolist' => [
            'profile' => 'Profile', // TODO(i18n): translate
            'signoffs' => 'Sign-offs', // TODO(i18n): translate
            'line_items' => 'Line items', // TODO(i18n): translate
        ],
        'actions' => [
            'create' => 'New purchase order', // TODO(i18n): translate
            'edit' => 'Edit', // TODO(i18n): translate
            'view' => 'View', // TODO(i18n): translate
            'delete' => 'Delete', // TODO(i18n): translate
            'restore' => 'Restore', // TODO(i18n): translate
            'force_delete' => 'Delete permanently', // TODO(i18n): translate
            'order' => 'Order', // TODO(i18n): translate
            'order_heading' => 'Place order', // TODO(i18n): translate
            'order_description' => 'Send this purchase order to the supplier.', // TODO(i18n): translate
            'receive' => 'Receive',
            'receive_heading' => 'Receive purchase', // TODO(i18n): translate
            'receive_description' => 'Record received base quantities.', // TODO(i18n): translate
            'cancel' => 'Cancel', // TODO(i18n): translate
            'cancel_heading' => 'Cancel purchase order', // TODO(i18n): translate
            'cancel_description' => 'Cancel this purchase order.', // TODO(i18n): translate
            'more_actions' => 'More actions', // TODO(i18n): translate
        ],
        'notifications' => [
            'received' => 'Natanggap ang purchase order.',
        ],
    ],
    'suppliers' => [
        'model' => [
            'singular' => 'Supplier',
            'plural' => 'Suppliers',
            'singular_desc' => 'Import supplier records (Name, Contact Person, Phone, Email, Address).', // TODO(i18n): translate
        ],
        'navigation' => [
            'label' => 'Suppliers', // TODO(i18n): translate
        ],
        'fields' => [
            'name' => 'Name',
            'contact_person' => 'Contact Person',
            'phone' => 'Phone',
            'email' => 'Email',
            'address' => 'Address',
            'is_active' => 'Active', // TODO(i18n): translate
        ],
        'filters' => [
            'is_active' => 'Active', // TODO(i18n): translate
        ],
        'table' => [
            'name' => 'Name', // TODO(i18n): translate
            'contact' => 'Contact', // TODO(i18n): translate
            'email' => 'Email', // TODO(i18n): translate
            'phone' => 'Phone', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
            'purchase_orders' => 'Purchase orders', // TODO(i18n): translate
            'empty_heading' => 'No suppliers found', // TODO(i18n): translate
            'empty_description' => 'Create your first supplier to get started.', // TODO(i18n): translate
        ],
        'infolist' => [
            'profile' => 'Supplier profile', // TODO(i18n): translate
            'purchase_orders' => 'Purchase orders', // TODO(i18n): translate
        ],
        'actions' => [
            'create' => 'New supplier', // TODO(i18n): translate
            'edit' => 'Edit', // TODO(i18n): translate
            'delete' => 'Delete', // TODO(i18n): translate
            'restore' => 'Restore', // TODO(i18n): translate
            'force_delete' => 'Delete permanently', // TODO(i18n): translate
            'import' => 'Import', // TODO(i18n): translate
            'import_heading' => 'Import suppliers', // TODO(i18n): translate
            'import_description' => 'Controlled import with template download, column mapping, row preview and confirmation.', // TODO(i18n): translate
        ],
        'import' => [
            'desc_name' => 'Supplier name. Potential matches with existing records are flagged for review and never merged.', // TODO(i18n): translate
            'desc_contact_person' => 'Optional contact person.', // TODO(i18n): translate
            'desc_phone' => 'Optional phone number.', // TODO(i18n): translate
            'desc_email' => 'Optional email address.', // TODO(i18n): translate
            'desc_address' => 'Optional address.', // TODO(i18n): translate
            'errors' => [
                'name_required' => 'Supplier name is required.', // TODO(i18n): translate
                'duplicate_name_in_file' => 'Duplicate supplier name ":name" in the uploaded file. Rejected for review.', // TODO(i18n): translate
                'possible_match' => 'A supplier named ":name" already exists (id :id). Flagged for review — imports never merge or update by name.', // TODO(i18n): translate
            ],
        ],
    ],
    'sales_orders' => [
        'model' => [
            'singular' => 'Sales Order',
            'plural' => 'Sales Orders',
        ],
        'navigation' => [
            'label' => 'Sales Orders', // TODO(i18n): translate
        ],
        'badge_tooltip' => 'Orders awaiting dispatch', // TODO(i18n): translate
        'form' => [
            'customer_warehouse' => 'Customer & warehouse', // TODO(i18n): translate
        ],
        'steps' => [
            'customer_warehouse' => 'Customer & Warehouse',
            'customer_warehouse_description' => 'Choose customer and dispatching warehouse.', // TODO(i18n): translate
            'line_items' => 'Line Items (Read-Only Sale Price Preview)',
            'line_items_description' => 'Add sold line items.', // TODO(i18n): translate
            'review_verify' => 'Review & Notes',
            'review_verify_description' => 'Verify before creating.', // TODO(i18n): translate
        ],
        'fields' => [
            'customer' => 'Customer',
            'dispatching_warehouse' => 'Dispatching warehouse', // TODO(i18n): translate
            'variant_sku' => 'Variant SKU', // TODO(i18n): translate
            'sku' => 'SKU', // TODO(i18n): translate
            'product' => 'Product', // TODO(i18n): translate
            'unit' => 'Unit', // TODO(i18n): translate
            'ratio_base' => 'Ratio (base)', // TODO(i18n): translate
            'qty' => 'Qty', // TODO(i18n): translate
            'ordered_base' => 'Ordered (base)', // TODO(i18n): translate
            'dispatched_base' => 'Dispatched (base)', // TODO(i18n): translate
            'snapshot_price' => 'Snapshot price', // TODO(i18n): translate
            'catalog_sale_price' => 'Catalog sale price', // TODO(i18n): translate
            'notes' => 'Notes', // TODO(i18n): translate
            'reference_code' => 'Reference', // TODO(i18n): translate
            'ordered_by' => 'Ordered by', // TODO(i18n): translate
            'ordered_at' => 'Ordered at', // TODO(i18n): translate
            'dispatched_by' => 'Dispatched by', // TODO(i18n): translate
            'confirmed_at' => 'Confirmed at', // TODO(i18n): translate
            'dispatched_at' => 'Dispatched at', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
            'line_item' => 'Line item', // TODO(i18n): translate
            'dispatch_line' => ':sku — outstanding :outstanding :unit (available :available)', // TODO(i18n): translate
            'return_option' => ':sku — dispatched :dispatched, returned :returned', // TODO(i18n): translate
            'returned_qty_base' => 'Returned qty (base)', // TODO(i18n): translate
        ],
        'hints' => [
            'ratio_auto' => 'Ratio is resolved automatically from the unit.', // TODO(i18n): translate
        ],
        'help' => [
            'reference_code' => 'Auto-generated when left empty.', // TODO(i18n): translate
            'no_stock' => 'No stock available for this line.', // TODO(i18n): translate
            'insufficient_stock' => 'Available stock is below the outstanding quantity.', // TODO(i18n): translate
        ],
        'placeholders' => [
            'reference_code' => 'Auto-generated when empty', // TODO(i18n): translate
        ],
        'filters' => [
            'customer' => 'Customer', // TODO(i18n): translate
            'warehouse' => 'Warehouse', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
        ],
        'table' => [
            'reference' => 'Reference', // TODO(i18n): translate
            'customer' => 'Customer', // TODO(i18n): translate
            'warehouse' => 'Warehouse', // TODO(i18n): translate
            'items' => 'Items', // TODO(i18n): translate
            'confirmed' => 'Confirmed', // TODO(i18n): translate
            'dispatched' => 'Dispatched', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
            'empty_heading' => 'No sales orders found', // TODO(i18n): translate
            'empty_description' => 'Create your first sales order to get started.', // TODO(i18n): translate
        ],
        'infolist' => [
            'profile' => 'Profile', // TODO(i18n): translate
            'signoffs' => 'Sign-offs', // TODO(i18n): translate
            'line_items' => 'Line items', // TODO(i18n): translate
        ],
        'actions' => [
            'create' => 'New sales order', // TODO(i18n): translate
            'edit' => 'Edit', // TODO(i18n): translate
            'view' => 'View', // TODO(i18n): translate
            'delete' => 'Delete', // TODO(i18n): translate
            'restore' => 'Restore', // TODO(i18n): translate
            'force_delete' => 'Delete permanently', // TODO(i18n): translate
            'confirm' => 'Confirm', // TODO(i18n): translate
            'confirm_heading' => 'Confirm sales order', // TODO(i18n): translate
            'confirm_description' => 'Confirm and reserve stock for this order.', // TODO(i18n): translate
            'dispatch' => 'Dispatch',
            'dispatch_heading' => 'Dispatch sale', // TODO(i18n): translate
            'dispatch_description' => 'Dispatch quantities against reservations.', // TODO(i18n): translate
            'return' => 'Return', // TODO(i18n): translate
            'return_heading' => 'Record sales return', // TODO(i18n): translate
            'return_description' => 'Record returned base quantities.', // TODO(i18n): translate
            'cancel' => 'Cancel', // TODO(i18n): translate
            'cancel_heading' => 'Cancel sales order', // TODO(i18n): translate
            'cancel_description' => 'Cancel this sales order.', // TODO(i18n): translate
            'more_actions' => 'More actions', // TODO(i18n): translate
        ],
        'notifications' => [
            'dispatched' => 'Ipinadala ang sales order.',
            'return_recorded' => 'Return recorded.', // TODO(i18n): translate
        ],
    ],
    'customers' => [
        'model' => [
            'singular' => 'Customer',
            'plural' => 'Customers',
            'singular_desc' => 'Import customer records (Name, Contact Person, Phone, Email, Address).', // TODO(i18n): translate
        ],
        'navigation' => [
            'label' => 'Customers', // TODO(i18n): translate
        ],
        'fields' => [
            'name' => 'Name',
            'contact_person' => 'Contact Person',
            'phone' => 'Phone',
            'email' => 'Email',
            'address' => 'Address',
            'is_active' => 'Active', // TODO(i18n): translate
        ],
        'filters' => [
            'is_active' => 'Active', // TODO(i18n): translate
        ],
        'table' => [
            'name' => 'Name', // TODO(i18n): translate
            'contact' => 'Contact', // TODO(i18n): translate
            'email' => 'Email', // TODO(i18n): translate
            'phone' => 'Phone', // TODO(i18n): translate
            'status' => 'Status', // TODO(i18n): translate
            'sales_orders' => 'Sales orders', // TODO(i18n): translate
            'empty_heading' => 'No customers found', // TODO(i18n): translate
            'empty_description' => 'Create your first customer to get started.', // TODO(i18n): translate
        ],
        'infolist' => [
            'profile' => 'Customer profile', // TODO(i18n): translate
            'sales_orders' => 'Sales orders', // TODO(i18n): translate
        ],
        'actions' => [
            'create' => 'New customer', // TODO(i18n): translate
            'edit' => 'Edit', // TODO(i18n): translate
            'delete' => 'Delete', // TODO(i18n): translate
            'restore' => 'Restore', // TODO(i18n): translate
            'force_delete' => 'Delete permanently', // TODO(i18n): translate
            'import' => 'Import', // TODO(i18n): translate
            'import_heading' => 'Import customers', // TODO(i18n): translate
            'import_description' => 'Controlled import with template download, column mapping, row preview and confirmation.', // TODO(i18n): translate
        ],
        'import' => [
            'desc_name' => 'Customer name. Potential matches with existing records are flagged for review and never merged.', // TODO(i18n): translate
            'desc_contact_person' => 'Optional contact person.', // TODO(i18n): translate
            'desc_phone' => 'Optional phone number.', // TODO(i18n): translate
            'desc_email' => 'Optional email address.', // TODO(i18n): translate
            'desc_address' => 'Optional address.', // TODO(i18n): translate
            'errors' => [
                'name_required' => 'Customer name is required.', // TODO(i18n): translate
                'duplicate_name_in_file' => 'Duplicate customer name ":name" in the uploaded file. Rejected for review.', // TODO(i18n): translate
                'possible_match' => 'A customer named ":name" already exists (id :id). Flagged for review — imports never merge or update by name.', // TODO(i18n): translate
            ],
        ],
    ],
];
