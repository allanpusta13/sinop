<?php

declare(strict_types=1);

return [
    'transfer_requisition_status' => [
        'draft' => 'Draft', // TODO(i18n): translate
        'requested' => 'Requested', // TODO(i18n): translate
        'under_review_fulfiller' => 'Under review (fulfiller)', // TODO(i18n): translate
        'under_review_requestor' => 'Under review (requestor)', // TODO(i18n): translate
        'confirmed' => 'Confirmed', // TODO(i18n): translate
        'dispatched' => 'Dispatched', // TODO(i18n): translate
        'partially_received' => 'Partially received', // TODO(i18n): translate
        'completed' => 'Completed', // TODO(i18n): translate
        'closed_with_loss' => 'Closed with loss', // TODO(i18n): translate
        'cancelled' => 'Cancelled', // TODO(i18n): translate
    ],
    'purchase_order_status' => [
        'draft' => 'Draft', // TODO(i18n): translate
        'ordered' => 'Ordered', // TODO(i18n): translate
        'partially_received' => 'Partially received', // TODO(i18n): translate
        'received' => 'Received', // TODO(i18n): translate
        'cancelled' => 'Cancelled', // TODO(i18n): translate
    ],
    'sales_order_status' => [
        'draft' => 'Draft', // TODO(i18n): translate
        'confirmed' => 'Confirmed', // TODO(i18n): translate
        'partially_dispatched' => 'Partially dispatched', // TODO(i18n): translate
        'dispatched' => 'Dispatched', // TODO(i18n): translate
        'cancelled' => 'Cancelled', // TODO(i18n): translate
    ],
    'stock_movement_type' => [
        'transfer_in' => 'Transfer in', // TODO(i18n): translate
        'transfer_out' => 'Transfer out', // TODO(i18n): translate
        'purchase' => 'Purchase', // TODO(i18n): translate
        'purchase_return' => 'Purchase return', // TODO(i18n): translate
        'sale' => 'Sale', // TODO(i18n): translate
        'sale_return' => 'Sale return', // TODO(i18n): translate
        'adjustment' => 'Adjustment', // TODO(i18n): translate
        'loss' => 'Loss', // TODO(i18n): translate
        'damage' => 'Damage', // TODO(i18n): translate
    ],
    'revision_status' => [
        'pending' => 'Pending', // TODO(i18n): translate
        'accepted' => 'Accepted', // TODO(i18n): translate
        'rejected' => 'Rejected', // TODO(i18n): translate
    ],
    'negotiation_side' => [
        'requestor' => 'Requestor', // TODO(i18n): translate
        'fulfiller' => 'Fulfiller', // TODO(i18n): translate
    ],
    'in_transit_status' => [
        'in_transit' => 'In transit', // TODO(i18n): translate
        'cleared' => 'Cleared', // TODO(i18n): translate
        'lost' => 'Lost', // TODO(i18n): translate
    ],
    'loss_category' => [
        'shortfall' => 'Shortfall', // TODO(i18n): translate
        'damage' => 'Damage', // TODO(i18n): translate
        'spoilage' => 'Spoilage', // TODO(i18n): translate
        'theft' => 'Theft', // TODO(i18n): translate
        'other' => 'Other', // TODO(i18n): translate
    ],
    'user_role' => [
        'admin' => 'Admin', // TODO(i18n): translate
        'auditor' => 'Auditor', // TODO(i18n): translate
        'warehouse_staff' => 'Warehouse staff', // TODO(i18n): translate
        'branch_manager' => 'Branch manager', // TODO(i18n): translate
    ],
];
