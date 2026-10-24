<?php

declare(strict_types=1);

return [
    'insufficient_stock' => [
        'title' => 'Stock insuficiente',
        'body' => 'Solicitado :requested, disponible :available para la variante :variant.',
    ],
    'outstanding_quantity_exceeded' => [
        'title' => 'Cantidad pendiente excedida',
        'body' => 'Se intentó :attempted contra un pendiente de :outstanding para el artículo :item.',
    ],
    'invalid_document_state' => [
        'title' => 'Estado del documento no válido',
        'body' => 'La acción :action no está permitida mientras el estado sea :status.',
    ],
    'invalid_revision_transition' => [
        'title' => 'Transición de revisión no válida',
        'body' => 'No se puede mover la revisión de :actual a :target.',
    ],
    'product_family_has_variants' => [
        'title' => 'La familia de productos tiene variantes',
        'body' => 'El producto :product todavía tiene variantes y no se puede eliminar.',
    ],
    'negotiation_not_allowed' => [
        'title' => 'Negociación no permitida',
        'body' => 'La requisición :requisition no se puede negociar mientras :status.',
    ],
    'revision_already_resolved' => [
        'title' => 'Revisión ya resuelta',
        'body' => 'La revisión :revision ya ha sido resuelta.',
    ],
    'invalid_movement_type' => [
        'title' => 'Tipo de movimiento no válido',
        'body' => 'El tipo de movimiento :type no está permitido aquí.',
    ],
    'invalid_unit_ratio' => [
        'title' => 'Proporción de unidad no válida',
        'body' => 'La proporción de unidad :ratio no es válida.',
    ],
    'same_warehouse_transfer' => [
        'title' => 'Transferencia al mismo almacén',
        'body' => 'El almacén de origen y el de destino (:warehouse) deben ser diferentes.',
    ],
    'empty_transfer_items' => [
        'title' => 'Transferencia vacía',
        'body' => 'Una transferencia directa requiere al menos una línea de artículo.',
    ],
    'duplicate_transfer_variant' => [
        'title' => 'Variante duplicada en la transferencia',
        'body' => 'La línea :index repite la variante :variant; una transferencia solo contiene variantes distintas.',
    ],
    'empty_purchase_items' => [
        'title' => 'Orden de compra vacía',
        'body' => 'Una orden de compra requiere al menos una línea de artículo.',
    ],
    'empty_purchase_receipt' => [
        'title' => 'Recepción de compra vacía',
        'body' => 'No se proporcionaron cantidades de recepción.',
    ],
    'empty_sales_items' => [
        'title' => 'Orden de venta vacía',
        'body' => 'Una orden de venta requiere al menos una línea de artículo.',
    ],
    'empty_sales_dispatch' => [
        'title' => 'Despacho de venta vacío',
        'body' => 'No se proporcionaron cantidades de despacho.',
    ],
    'empty_requisition_items' => [
        'title' => 'Requisición vacía',
        'body' => 'Una requisición de transferencia requiere al menos un artículo del manifiesto.',
    ],
    'invalid_return_quantity' => [
        'title' => 'Cantidad de devolución no válida',
        'body' => 'La cantidad de devolución :qty para el artículo :item debe ser al menos 1.',
    ],
    'missing_item_field' => [
        'title' => 'Campo de artículo faltante',
        'body' => 'A la línea :index le falta :field.',
    ],
    'invalid_item_quantity' => [
        'title' => 'Cantidad de artículo no válida',
        'body' => 'La línea :index tiene una cantidad :qty no válida.',
    ],
    'unknown_variant' => [
        'title' => 'Variante desconocida',
        'body' => 'La variante :variant no existe.',
    ],
    'undefined_unit' => [
        'title' => 'Unidad no definida',
        'body' => 'La unidad :unit no está definida para la variante :variant.',
    ],
    'unit_ratio_mismatch' => [
        'title' => 'Discrepancia en la proporción de unidad',
        'body' => 'La línea :index declara la unidad :unit con una proporción incorrecta.',
    ],
    'unknown_requisition_item' => [
        'title' => 'Artículo de requisición desconocido',
        'body' => 'El artículo :item no pertenece a esta requisición.',
    ],
    'non_integer_payload' => [
        'title' => 'Carga útil no entera',
        'body' => 'La carga útil del escaneo para el artículo :item debe ser un número entero.',
    ],
    'negative_payload' => [
        'title' => 'Carga útil negativa',
        'body' => 'La carga útil del artículo :item no debe ser negativa.',
    ],
    'empty_loss' => [
        'title' => 'Pérdida vacía',
        'body' => 'La entrada de pérdida para el artículo :item registra una cantidad cero.',
    ],
    'invalid_proposed_quantity' => [
        'title' => 'Cantidad propuesta no válida',
        'body' => 'La cantidad propuesta :qty no es válida.',
    ],
    'cross_item_revision' => [
        'title' => 'Revisión entre artículos',
        'body' => 'La revisión :revision no pertenece al artículo :item.',
    ],
    'missing_approved_quantity' => [
        'title' => 'Cantidad aprobada faltante',
        'body' => 'No se ha establecido una cantidad aprobada.',
    ],
    'warehouse_out_of_scope' => [
        'title' => 'Almacén fuera del alcance',
        'body' => 'Los almacenes :from / :to están fuera de su asignación.',
    ],
    'warehouse_code_exhausted' => [
        'title' => 'Código de almacén agotado',
        'body' => 'No se pudo derivar un código único para :name.',
    ],
    'warehouse_assignment_count' => [
        'title' => 'Demasiados almacenes',
        'body' => 'El personal de almacén puede tener como máximo un almacén; se proporcionaron :count.',
    ],
    'reference_code_exhausted' => [
        'title' => 'Código de referencia agotado',
        'body' => 'No se pudo generar un código de referencia :prefix único después de :attempts intentos.',
    ],
];
