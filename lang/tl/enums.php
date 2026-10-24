<?php

declare(strict_types=1);

return [
    'transfer_requisition_status' => [
        'draft' => 'Burador',
        'requested' => 'Hiningi',
        'under_review_fulfiller' => 'Sinusuri (fulfiller)',
        'under_review_requestor' => 'Sinusuri (requestor)',
        'confirmed' => 'Kinumpirma',
        'dispatched' => 'Naipadala',
        'partially_received' => 'Bahagyang natanggap',
        'completed' => 'Natapos',
        'closed_with_loss' => 'Isinara na may pagkawala',
        'cancelled' => 'Kinansela',
    ],
    'purchase_order_status' => [
        'draft' => 'Burador',
        'ordered' => 'In-order',
        'partially_received' => 'Bahagyang natanggap',
        'received' => 'Natanggap',
        'cancelled' => 'Kinansela',
    ],
    'sales_order_status' => [
        'draft' => 'Burador',
        'confirmed' => 'Kinumpirma',
        'partially_dispatched' => 'Bahagyang naipadala',
        'dispatched' => 'Naipadala',
        'cancelled' => 'Kinansela',
    ],
    'stock_movement_type' => [
        'transfer_in' => 'Pagpasok ng transfer',
        'transfer_out' => 'Paglabas ng transfer',
        'purchase' => 'Pagbili',
        'purchase_return' => 'Pagbabalik ng pagbili',
        'sale' => 'Benta',
        'sale_return' => 'Pagbabalik ng benta',
        'adjustment' => 'Adjustment',
        'loss' => 'Pagkawala',
        'damage' => 'Pinsala',
    ],
    'revision_status' => [
        'pending' => 'Naghihintay',
        'accepted' => 'Tinanggap',
        'rejected' => 'Tinanggihan',
    ],
    'negotiation_side' => [
        'requestor' => 'Requestor',
        'fulfiller' => 'Fulfiller',
    ],
    'in_transit_status' => [
        'in_transit' => 'In-Transit',
        'cleared' => 'Na-clear',
        'lost' => 'Nawala',
    ],
    'loss_category' => [
        'shortfall' => 'Kakulangan',
        'damage' => 'Pinsala',
        'spoilage' => 'Pagkabulok',
        'theft' => 'Pagnanakaw',
        'other' => 'Iba pa',
    ],
    'user_role' => [
        'admin' => 'Admin',
        'auditor' => 'Auditor',
        'warehouse_staff' => 'Kawani ng bodega',
        'branch_manager' => 'Manager ng branch',
    ],
];
