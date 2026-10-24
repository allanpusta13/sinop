<?php

declare(strict_types=1);

return [
    // Common form elements
    'required' => 'Kinakailangan',
    'optional' => 'Opsyonal',
    'placeholder' => 'Pumili...',
    'select_option' => 'Pumili ng option',
    'search' => 'Maghanap...',
    'no_results' => 'Walang resulta',
    'loading' => 'Naglo-load...',
    'save' => 'I-save',
    'cancel' => 'Kanselahin',
    'submit' => 'Isumite',
    'reset' => 'I-reset',
    'add' => 'Magdagdag',
    'remove' => 'Tanggalin',
    'edit' => 'I-edit',
    'view' => 'Tingnan',
    'delete' => 'Burahin',
    'confirm' => 'Kumpirmahin',
    'back' => 'Bumalik',
    'next' => 'Susunod',
    'previous' => 'Nakaraang',
    'finish' => 'Tapusin',

    // Validation messages
    'validation_required' => 'Kinakailangan ang field na ito',
    'validation_min' => 'Minimum :min characters',
    'validation_max' => 'Maximum :max characters',
    'validation_numeric' => 'Dapat numero',
    'validation_integer' => 'Dapat integer',
    'validation_email' => 'Invalid na email',
    'validation_date' => 'Invalid na petsa',
    'validation_unique' => 'Umiiral na ang value na ito',
    'validation_exists' => 'Invalid ang napiling value',
    'validation_confirmed' => 'Hindi tumutugma ang kumpirmasyon',

    // Direct Transfer form
    'direct_transfer' => [
        'step1_title' => 'Pagmamapa ng Lokasyon',
        'step1_desc' => 'Pumili ng pinagmulan at paroroonang bodega para sa transfer.',
        'step2_title' => 'Paglalaan ng Stock',
        'step2_desc' => 'Magdagdag ng mga variant ng produkto at dami na ililipat.',
        'step3_title' => 'Suriin at I-verify',
        'step3_desc' => 'Suriin ang buong summary ng transfer bago isagawa.',

        'sections' => [
            'routing' => 'Ruta ng Bodega',
        ],

        'fields' => [
            'origin_warehouse' => 'Bodegang pinagmulan',
            'destination_warehouse' => 'Bodegang paroroonan',
            'items' => 'Mga Item',
            'product_variant' => 'Variant ng Produkto',
            'unit_name' => 'Yunit',
            'unit_ratio' => 'Rasyo (base)',
            'qty' => 'Dami',
            'base_qty' => 'Dami ng Base (Kinakalkula)',
            'line_notes' => 'Mga Tala ng Linya',
            'line_notes_placeholder' => 'Opsyonal na tala para sa linya',
            'transfer_notes' => 'Mga Tala ng Transfer',
            'transfer_notes_placeholder' => 'Mga tala para sa buong transfer',
        ],

        'hints' => [
            'ratio_auto' => 'Awtomatikong pinupunan batay sa napiling yunit',
        ],

        'add_line' => 'Magdagdag ng Linya',

        'review_verify' => 'Suriin at I-verify',

        'complete_previous_steps' => 'Tapusin ang mga naunang hakbang para makita ang summary.',
        'origin' => 'Pinagmulan',
        'destination' => 'Paroroonan',
        'sku' => 'SKU',
        'variant' => 'Variant',
        'qty_unit' => 'Dami / Yunit',
        'base_units' => 'Mga Base Unit',
    ],

    // Transfer Requisition form
    'transfer_requisition' => [
        'step1_title' => 'Ruta',
        'step1_desc' => 'Pumili ng pinagmulan at paroroonang bodega.',
        'step2_title' => 'Mga Item',
        'step2_desc' => 'Magdagdag ng mga variant ng produkto at hiniling na dami.',
        'step3_title' => 'Pagsusuri',
        'step3_desc' => 'Suriin ang buong requisition bago ipadala.',

        'fields' => [
            'from_warehouse' => 'Bodegang pinagmulan',
            'to_warehouse' => 'Bodegang paroroonan',
            'items' => 'Mga Item',
            'product_variant' => 'Variant ng Produkto',
            'unit_name' => 'Yunit',
            'unit_ratio' => 'Rasyo (base)',
            'qty' => 'Dami',
            'base_qty' => 'Dami ng Base',
            'line_notes' => 'Mga Tala ng Linya',
        ],
    ],

    // Purchase Order form
    'purchase_order' => [
        'step1_title' => 'Supplier at Bodega',
        'step1_desc' => 'Pumili ng supplier at receiving na bodega.',
        'step2_title' => 'Mga Line Item',
        'step2_desc' => 'Magdagdag ng mga variant ng produkto, yunit, at dami.',
        'step3_title' => 'Pagsusuri at Tala',
        'step3_desc' => 'Suriin ang buong order at magdagdag ng mga tala.',

        'fields' => [
            'supplier' => 'Supplier',
            'warehouse' => 'Receiving na Bodega',
            'items' => 'Mga Linya',
            'product_variant' => 'Variant ng Produkto',
            'unit_name' => 'Yunit',
            'unit_ratio' => 'Rasyo (base)',
            'qty' => 'Dami',
            'base_qty' => 'Dami ng Base',
            'unit_cost' => 'Gastos ng Yunit',
            'line_notes' => 'Mga Tala ng Linya',
            'order_notes' => 'Mga Tala ng Order',
        ],
    ],

    // Sales Order form
    'sales_order' => [
        'step1_title' => 'Customer at Bodega',
        'step1_desc' => 'Pumili ng customer at dispatch na bodega.',
        'step2_title' => 'Line Items (Read-Only Sale Price Preview)',
        'step2_desc' => 'Suriin ang mga linya na may nakalakip na presyo ng benta.',
        'step3_title' => 'Pagsusuri at Tala',
        'step3_desc' => 'Suriin ang buong order at magdagdag ng mga tala.',

        'fields' => [
            'customer' => 'Customer',
            'warehouse' => 'Dispatching na Bodega',
            'items' => 'Mga Linya',
            'product_variant' => 'Variant ng Produkto',
            'unit_name' => 'Yunit',
            'unit_ratio' => 'Rasyo (base)',
            'qty' => 'Dami',
            'base_qty' => 'Dami ng Base',
            'sale_price' => 'Presyo ng Benta',
            'line_notes' => 'Mga Tala ng Linya',
            'order_notes' => 'Mga Tala ng Order',
        ],
    ],

    // Product form
    'product' => [
        'sections' => [
            'details' => 'Detalye',
            'inventory' => 'Imbentaryo',
            'pricing' => 'Pagpepresyo',
        ],
        'fields' => [
            'sku' => 'SKU',
            'barcode' => 'Barcode',
            'name' => 'Pangalan',
            'description' => 'Paglalarawan',
            'base_unit' => 'Base Unit',
            'reorder_point' => 'Reorder Point',
            'is_active' => 'Active',
        ],
    ],

    // Warehouse form
    'warehouse' => [
        'sections' => [
            'details' => 'Detalye',
        ],
        'fields' => [
            'code' => 'Kodigo',
            'name' => 'Pangalan',
            'location' => 'Lokasyon',
            'is_active' => 'Active',
        ],
    ],

    // Supplier form
    'supplier' => [
        'fields' => [
            'name' => 'Pangalan',
            'contact_person' => 'Taong Kontak',
            'email' => 'Email',
            'phone' => 'Telepono',
            'address' => 'Adres',
        ],
    ],

    // Customer form
    'customer' => [
        'fields' => [
            'name' => 'Pangalan',
            'contact_person' => 'Taong Kontak',
            'email' => 'Email',
            'phone' => 'Telepono',
            'address' => 'Adres',
        ],
    ],

    // User form
    'user' => [
        'fields' => [
            'name' => 'Pangalan',
            'email' => 'Email',
            'role' => 'Role',
            'warehouses' => 'Mga Nakatalagang Bodega',
            'password' => 'Password',
            'password_confirmation' => 'Kumpirmahin ang Password',
        ],
    ],
];
