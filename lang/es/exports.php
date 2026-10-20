<?php

declare(strict_types=1);

/**
 * Traducciones de la suite de exportación (#225 / #226).
 *
 * Las cadenas genéricas de la acción viven en `action` — hay una
 * acción Export por página List, así que las etiquetas por recurso
 * solo se duplicarían. Las etiquetas de tipo (`types.*`) reflejan las
 * 19 claves del registro en config/exports.php; las etiquetas de
 * columnas estructurales (`columns.*`) cubren los ids y campos de
 * referencia que ninguna clave de campo del recurso describe.
 */
return [

    'action' => [
        'label' => 'Exportar',
        'heading' => 'Exportar datos',
        'description' => 'Elige qué exportar y el formato del archivo. La exportación sigue los filtros actuales de la tabla y tu acceso a los almacenes.',
        'submit_label' => 'Exportar',
        'type_label' => 'Datos a exportar',
        'format_label' => 'Formato del archivo',
    ],

    'notifications' => [
        'completed_title' => 'Exportación lista',
        'completed_body' => 'La exportación de :type terminó: :rows filas. Descarga el archivo para conservar una copia.',
        'queued_title' => 'Exportación en cola',
        'queued_body' => 'La exportación de :type (:rows filas) se está generando. Recibirás una notificación con el enlace de descarga cuando esté lista.',
        'download_label' => 'Descargar archivo',
        'failed_title' => 'Falló la exportación',
        'failed_body' => 'No se pudo generar la exportación. Inténtalo de nuevo.',
        'scope_lost_title' => 'Exportación cancelada',
        'scope_lost_body' => 'Tu acceso a los almacenes cambió mientras se generaba la exportación de :type y ya no queda ninguno del que leer. No se creó ningún archivo.',
    ],

    'errors' => [
        'unknown_definition' => 'Tipo de exportación desconocido: :key',
        'unsupported_format' => 'Formato de exportación no compatible.',
        'invalid_filename' => 'Nombre de archivo de exportación no válido.',
    ],

    'types' => [
        'products' => [
            'families' => 'Familias de productos',
            'variants' => 'Variantes de productos',
            'current_prices' => 'Precios actuales',
            'unit_conversions' => 'Conversiones de unidades',
        ],
        'warehouses' => 'Almacenes',
        'suppliers' => 'Proveedores',
        'customers' => 'Clientes',
        'stock_movements' => 'Movimientos de inventario',
        'loss_ledgers' => 'Registros del libro de pérdidas',
        'transfer_requisitions' => [
            'label' => 'Requisiciones de transferencia',
            'items' => 'Ítems de la requisición de transferencia',
            'revisions' => 'Revisiones de la requisición de transferencia',
        ],
        'in_transits' => 'Registros en tránsito',
        'purchase_orders' => [
            'label' => 'Órdenes de compra',
            'items' => 'Ítems de la orden de compra',
        ],
        'sales_orders' => [
            'label' => 'Órdenes de venta',
            'items' => 'Ítems de la orden de venta',
        ],
        'direct_transfers' => [
            'label' => 'Transferencias directas',
            'items' => 'Ítems de la transferencia directa',
        ],
    ],

    'columns' => [
        'effective_from' => 'Vigente desde',
        'movement_id' => 'ID del movimiento',
        'loss_id' => 'ID de la pérdida',
        'revision_id' => 'ID de la revisión',
        'item_id' => 'ID del ítem',
        'transfer_requisition_id' => 'ID de la requisición de transferencia',
        'transfer_requisition_item_id' => 'ID del ítem de la requisición de transferencia',
        'purchase_order_id' => 'ID de la orden de compra',
        'sales_order_id' => 'ID de la orden de venta',
        'direct_transfer_id' => 'ID de la transferencia directa',
        'product_variant_id' => 'ID de la variante de producto',
        'substitute_product_variant_id' => 'ID de la variante sustituta',
        'warehouse_id' => 'ID del almacén',
        'from_warehouse_id' => 'ID del almacén de origen',
        'to_warehouse_id' => 'ID del almacén de destino',
        'supplier_id' => 'ID del proveedor',
        'customer_id' => 'ID del cliente',
        'unit_ratio_used' => 'Ratio de unidad usado',
        'related_movement_id' => 'ID del movimiento relacionado',
        'reference_type' => 'Tipo de referencia',
        'reference_id' => 'ID de la referencia',
        'requested_unit' => 'Unidad solicitada',
        'approved_unit' => 'Unidad aprobada',
        'proposed_unit' => 'Unidad propuesta',
    ],

];
