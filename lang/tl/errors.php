<?php

declare(strict_types=1);

return [
    'insufficient_stock' => [
        'title' => 'Hindi sapat ang stock',
        'body' => 'Hiniling :requested, available :available para sa variant :variant.',
    ],
    'outstanding_quantity_exceeded' => [
        'title' => 'Lumampas sa outstanding quantity',
        'body' => 'Sinubukang :attempted laban sa outstanding :outstanding para sa item :item.',
    ],
    'invalid_document_state' => [
        'title' => 'Invalid na estado ng dokumento',
        'body' => 'Hindi pinapayagan ang action :action habang :status ang katayuan.',
    ],
    'invalid_revision_transition' => [
        'title' => 'Invalid na revision transition',
        'body' => 'Hindi mailipat ang revision mula :actual papuntang :target.',
    ],
    'product_family_has_variants' => [
        'title' => 'May variants ang product family',
        'body' => 'May variants pa ang product :product at hindi maaaring tanggalin.',
    ],
    'negotiation_not_allowed' => [
        'title' => 'Hindi pinapayagan ang negotiation',
        'body' => 'Hindi maaaring i-negotiate ang requisition :requisition habang :status.',
    ],
    'revision_already_resolved' => [
        'title' => 'Naayos na ang revision',
        'body' => 'Naayos na ang revision :revision.',
    ],
    'invalid_movement_type' => [
        'title' => 'Invalid na uri ng galaw',
        'body' => 'Hindi pinapayagan ang movement type :type dito.',
    ],
    'invalid_unit_ratio' => [
        'title' => 'Invalid na unit ratio',
        'body' => 'Invalid ang unit ratio :ratio.',
    ],
    'same_warehouse_transfer' => [
        'title' => 'Parehong bodega ang transfer',
        'body' => 'Dapat magkaiba ang source at destination warehouse :warehouse.',
    ],
    'empty_transfer_items' => [
        'title' => 'Walang laman ang transfer',
        'body' => 'Kailangan ng direct transfer ng hindi bababa sa isang line item.',
    ],
    'duplicate_transfer_variant' => [
        'title' => 'Duplicate na transfer variant',
        'body' => 'Inuulit ng line :index ang variant :variant; dapat natatanging variants lamang ang nasa isang transfer.',
    ],
    'empty_purchase_items' => [
        'title' => 'Walang laman ang purchase order',
        'body' => 'Kailangan ng purchase order ng hindi bababa sa isang line item.',
    ],
    'empty_purchase_receipt' => [
        'title' => 'Walang laman ang purchase receipt',
        'body' => 'Walang naibigay na receipt quantities.',
    ],
    'empty_sales_items' => [
        'title' => 'Walang laman ang sales order',
        'body' => 'Kailangan ng sales order ng hindi bababa sa isang line item.',
    ],
    'empty_sales_dispatch' => [
        'title' => 'Walang laman ang sales dispatch',
        'body' => 'Walang naibigay na dispatch quantities.',
    ],
    'empty_requisition_items' => [
        'title' => 'Walang laman ang requisition',
        'body' => 'Kailangan ng transfer requisition ng hindi bababa sa isang manifest item.',
    ],
    'invalid_return_quantity' => [
        'title' => 'Invalid na return quantity',
        'body' => 'Dapat hindi bababa sa 1 ang return quantity :qty para sa item :item.',
    ],
    'missing_item_field' => [
        'title' => 'Kulang na field ng item',
        'body' => 'Kulang ng :field ang line :index.',
    ],
    'invalid_item_quantity' => [
        'title' => 'Invalid na item quantity',
        'body' => 'May invalid na quantity :qty ang line :index.',
    ],
    'unknown_variant' => [
        'title' => 'Hindi kilalang variant',
        'body' => 'Hindi umiiral ang variant :variant.',
    ],
    'undefined_unit' => [
        'title' => 'Hindi tinukoy na unit',
        'body' => 'Hindi tinukoy ang unit :unit para sa variant :variant.',
    ],
    'unit_ratio_mismatch' => [
        'title' => 'Hindi tugma ang unit ratio',
        'body' => 'Idinedeklara ng line :index ang unit :unit na may maling ratio.',
    ],
    'unknown_requisition_item' => [
        'title' => 'Hindi kilalang requisition item',
        'body' => 'Hindi kabilang ang item :item sa requisition na ito.',
    ],
    'non_integer_payload' => [
        'title' => 'Hindi integer ang payload',
        'body' => 'Dapat integer ang scan payload para sa item :item.',
    ],
    'negative_payload' => [
        'title' => 'Negatibong payload',
        'body' => 'Hindi dapat negatibo ang payload para sa item :item.',
    ],
    'empty_loss' => [
        'title' => 'Walang laman ang loss',
        'body' => 'Nagtatala ng zero quantity ang loss entry para sa item :item.',
    ],
    'invalid_proposed_quantity' => [
        'title' => 'Invalid na proposed quantity',
        'body' => 'Invalid ang proposed quantity :qty.',
    ],
    'cross_item_revision' => [
        'title' => 'Cross-item na revision',
        'body' => 'Hindi kabilang ang revision :revision sa item :item.',
    ],
    'missing_approved_quantity' => [
        'title' => 'Kulang na approved quantity',
        'body' => 'Walang nakatakdang approved quantity.',
    ],
    'warehouse_out_of_scope' => [
        'title' => 'Wala sa saklaw ang bodega',
        'body' => 'Ang mga warehouse :from / :to ay wala sa iyong assignment.',
    ],
    'warehouse_code_exhausted' => [
        'title' => 'Naubos ang warehouse code',
        'body' => 'Hindi makabuo ng natatanging code para sa :name.',
    ],
    'warehouse_assignment_count' => [
        'title' => 'Sobrang dami ng warehouse',
        'body' => 'Maaari lamang maghawak ang WarehouseStaff ng isang warehouse; :count ang ibinigay.',
    ],
    'reference_code_exhausted' => [
        'title' => 'Naubos ang reference code',
        'body' => 'Hindi makabuo ng natatanging :prefix reference code pagkatapos ng :attempts subok.',
    ],
];
