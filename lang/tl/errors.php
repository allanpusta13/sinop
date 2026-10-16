<?php

declare(strict_types=1);

return [
    'insufficient_stock' => [
        'title' => 'Insufficient stock', // TODO(i18n): translate
        'body' => 'Requested :requested, available :available for variant :variant.', // TODO(i18n): translate
    ],
    'outstanding_quantity_exceeded' => [
        'title' => 'Outstanding quantity exceeded', // TODO(i18n): translate
        'body' => 'Attempted :attempted against outstanding :outstanding for item :item.', // TODO(i18n): translate
    ],
    'invalid_document_state' => [
        'title' => 'Invalid document state', // TODO(i18n): translate
        'body' => 'Action :action is not allowed while status is :status.', // TODO(i18n): translate
    ],
    'invalid_revision_transition' => [
        'title' => 'Invalid revision transition', // TODO(i18n): translate
        'body' => 'Cannot move revision from :actual to :target.', // TODO(i18n): translate
    ],
    'product_family_has_variants' => [
        'title' => 'Product family has variants', // TODO(i18n): translate
        'body' => 'Product :product still has variants and cannot be deleted.', // TODO(i18n): translate
    ],
    'negotiation_not_allowed' => [
        'title' => 'Negotiation not allowed', // TODO(i18n): translate
        'body' => 'Requisition :requisition cannot be negotiated while :status.', // TODO(i18n): translate
    ],
    'revision_already_resolved' => [
        'title' => 'Revision already resolved', // TODO(i18n): translate
        'body' => 'Revision :revision has already been resolved.', // TODO(i18n): translate
    ],
    'invalid_movement_type' => [
        'title' => 'Invalid movement type', // TODO(i18n): translate
        'body' => 'Movement type :type is not allowed here.', // TODO(i18n): translate
    ],
    'invalid_unit_ratio' => [
        'title' => 'Invalid unit ratio', // TODO(i18n): translate
        'body' => 'Unit ratio :ratio is invalid.', // TODO(i18n): translate
    ],
    'same_warehouse_transfer' => [
        'title' => 'Same warehouse transfer', // TODO(i18n): translate
        'body' => 'Source and destination warehouse :warehouse must differ.', // TODO(i18n): translate
    ],
    'empty_transfer_items' => [
        'title' => 'Empty transfer', // TODO(i18n): translate
        'body' => 'A direct transfer requires at least one line item.', // TODO(i18n): translate
    ],
    'duplicate_transfer_variant' => [
        'title' => 'Duplicate transfer variant', // TODO(i18n): translate
        'body' => 'Line :index repeats variant :variant; a transfer contains distinct variants only.', // TODO(i18n): translate
    ],
    'empty_purchase_items' => [
        'title' => 'Empty purchase order', // TODO(i18n): translate
        'body' => 'A purchase order requires at least one line item.', // TODO(i18n): translate
    ],
    'empty_purchase_receipt' => [
        'title' => 'Empty purchase receipt', // TODO(i18n): translate
        'body' => 'No receipt quantities were provided.', // TODO(i18n): translate
    ],
    'empty_sales_items' => [
        'title' => 'Empty sales order', // TODO(i18n): translate
        'body' => 'A sales order requires at least one line item.', // TODO(i18n): translate
    ],
    'empty_sales_dispatch' => [
        'title' => 'Empty sales dispatch', // TODO(i18n): translate
        'body' => 'No dispatch quantities were provided.', // TODO(i18n): translate
    ],
    'empty_requisition_items' => [
        'title' => 'Empty requisition', // TODO(i18n): translate
        'body' => 'A transfer requisition requires at least one manifest item.', // TODO(i18n): translate
    ],
    'invalid_return_quantity' => [
        'title' => 'Invalid return quantity', // TODO(i18n): translate
        'body' => 'Return quantity :qty for item :item must be at least 1.', // TODO(i18n): translate
    ],
    'missing_item_field' => [
        'title' => 'Missing item field', // TODO(i18n): translate
        'body' => 'Line :index is missing :field.', // TODO(i18n): translate
    ],
    'invalid_item_quantity' => [
        'title' => 'Invalid item quantity', // TODO(i18n): translate
        'body' => 'Line :index has invalid quantity :qty.', // TODO(i18n): translate
    ],
    'unknown_variant' => [
        'title' => 'Unknown variant', // TODO(i18n): translate
        'body' => 'Variant :variant does not exist.', // TODO(i18n): translate
    ],
    'undefined_unit' => [
        'title' => 'Undefined unit', // TODO(i18n): translate
        'body' => 'Unit :unit is not defined for variant :variant.', // TODO(i18n): translate
    ],
    'unit_ratio_mismatch' => [
        'title' => 'Unit ratio mismatch', // TODO(i18n): translate
        'body' => 'Line :index declares unit :unit with a wrong ratio.', // TODO(i18n): translate
    ],
    'unknown_requisition_item' => [
        'title' => 'Unknown requisition item', // TODO(i18n): translate
        'body' => 'Item :item does not belong to this requisition.', // TODO(i18n): translate
    ],
    'non_integer_payload' => [
        'title' => 'Non-integer payload', // TODO(i18n): translate
        'body' => 'Scan payload for item :item must be an integer.', // TODO(i18n): translate
    ],
    'negative_payload' => [
        'title' => 'Negative payload', // TODO(i18n): translate
        'body' => 'Payload for item :item must not be negative.', // TODO(i18n): translate
    ],
    'empty_loss' => [
        'title' => 'Empty loss', // TODO(i18n): translate
        'body' => 'Loss entry for item :item records zero quantity.', // TODO(i18n): translate
    ],
    'invalid_proposed_quantity' => [
        'title' => 'Invalid proposed quantity', // TODO(i18n): translate
        'body' => 'Proposed quantity :qty is invalid.', // TODO(i18n): translate
    ],
    'cross_item_revision' => [
        'title' => 'Cross-item revision', // TODO(i18n): translate
        'body' => 'Revision :revision does not belong to item :item.', // TODO(i18n): translate
    ],
    'missing_approved_quantity' => [
        'title' => 'Missing approved quantity', // TODO(i18n): translate
        'body' => 'No approved quantity is set.', // TODO(i18n): translate
    ],
    'warehouse_out_of_scope' => [
        'title' => 'Warehouse out of scope', // TODO(i18n): translate
        'body' => 'Warehouses :from / :to are outside your assignment.', // TODO(i18n): translate
    ],
    'warehouse_code_exhausted' => [
        'title' => 'Warehouse code exhausted', // TODO(i18n): translate
        'body' => 'Could not derive a unique code for :name.', // TODO(i18n): translate
    ],
    'warehouse_assignment_count' => [
        'title' => 'Too many warehouses', // TODO(i18n): translate
        'body' => 'WarehouseStaff may hold at most one warehouse; :count were given.', // TODO(i18n): translate
    ],
    'reference_code_exhausted' => [
        'title' => 'Naubos ang reference code',
        'body' => 'Hindi makabuo ng natatanging :prefix reference code pagkatapos ng :attempts subok.',
    ],
];
