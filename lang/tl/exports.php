<?php

declare(strict_types=1);

/**
 * Mga salin ng export suite (#225 / #226).
 *
 * Nasa `action` ang mga generic na string ng aksyon — may isang Export
 * aksyon bawat List page, kaya hindi na kailangan ng hiwalay na label
 * bawat resource. Ang mga label ng uri (`types.*`) ay tumutugma sa 19
 * key ng registry sa config/exports.php; ang mga label ng
 * structural na column (`columns.*`) ay para sa mga id at reference
 * field na walang katugmang key sa resource.
 */
return [

    'action' => [
        'label' => 'I-export',
        'heading' => 'Mag-export ng data',
        'description' => 'Piliin ang i-export at ang format ng file. Sumusunod ang export sa mga kasalukuyang filter ng table at sa iyong access sa mga bodega.',
        'submit_label' => 'I-export',
        'type_label' => 'Data na i-export',
        'format_label' => 'Format ng file',
    ],

    'notifications' => [
        'completed_title' => 'Handa na ang export',
        'completed_body' => 'Natapos ang export ng :type — :rows rows. I-download ang file para magkaroon ng kopya.',
        'queued_title' => 'Nakapila ang export',
        'queued_body' => 'Ginagawa ang export ng :type (:rows rows). Makakatanggap ka ng notification na may download link kapag handa na.',
        'download_label' => 'I-download ang file',
        'failed_title' => 'Nabigo ang export',
        'failed_body' => 'Hindi magawa ang export. Subukan muli.',
    ],

    'errors' => [
        'unknown_definition' => 'Hindi kilalang uri ng export: :key',
        'unsupported_format' => 'Hindi suportadong format ng export.',
        'invalid_filename' => 'Hindi wastong filename ng export.',
    ],

    'types' => [
        'products' => [
            'families' => 'Mga pamilya ng produkto',
            'variants' => 'Mga variant ng produkto',
            'current_prices' => 'Mga kasalukuyang presyo',
            'unit_conversions' => 'Mga conversion ng unit',
        ],
        'warehouses' => 'Mga bodega',
        'suppliers' => 'Mga supplier',
        'customers' => 'Mga customer',
        'stock_movements' => 'Mga galaw ng stock',
        'loss_ledgers' => 'Mga tala sa loss ledger',
        'transfer_requisitions' => [
            'label' => 'Mga transfer requisition',
            'items' => 'Mga item ng transfer requisition',
            'revisions' => 'Mga rebisyon ng transfer requisition',
        ],
        'in_transits' => 'Mga record na in-transit',
        'purchase_orders' => [
            'label' => 'Mga purchase order',
            'items' => 'Mga item ng purchase order',
        ],
        'sales_orders' => [
            'label' => 'Mga sales order',
            'items' => 'Mga item ng sales order',
        ],
        'direct_transfers' => [
            'label' => 'Mga direct transfer',
            'items' => 'Mga item ng direct transfer',
        ],
    ],

    'columns' => [
        'effective_from' => 'Epektibo mula',
        'movement_id' => 'ID ng galaw',
        'loss_id' => 'ID ng pagkawala',
        'revision_id' => 'ID ng rebisyon',
        'item_id' => 'ID ng item',
        'transfer_requisition_id' => 'ID ng transfer requisition',
        'transfer_requisition_item_id' => 'ID ng item ng transfer requisition',
        'purchase_order_id' => 'ID ng purchase order',
        'sales_order_id' => 'ID ng sales order',
        'direct_transfer_id' => 'ID ng direct transfer',
        'product_variant_id' => 'ID ng variant ng produkto',
        'substitute_product_variant_id' => 'ID ng panaltang variant',
        'warehouse_id' => 'ID ng bodega',
        'from_warehouse_id' => 'ID ng bodega pinanggalingan',
        'to_warehouse_id' => 'ID ng bodega paroroonan',
        'supplier_id' => 'ID ng supplier',
        'customer_id' => 'ID ng customer',
        'unit_ratio_used' => 'Ginamit na ratio ng unit',
        'related_movement_id' => 'ID ng kaugnay na galaw',
        'reference_type' => 'Uri ng reference',
        'reference_id' => 'ID ng reference',
        'requested_unit' => 'Hinihiling na unit',
        'approved_unit' => 'Aprubadong unit',
        'proposed_unit' => 'Iminumungkahing unit',
    ],

];
