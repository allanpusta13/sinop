<?php

declare(strict_types=1);

return [
    'transfer_requisition_status' => [
        'draft' => 'Borrador',
        'requested' => 'Solicitado',
        'under_review_fulfiller' => 'En revisión (proveedor interno)',
        'under_review_requestor' => 'En revisión (solicitante)',
        'confirmed' => 'Confirmado',
        'dispatched' => 'Despachado',
        'partially_received' => 'Recibido parcialmente',
        'completed' => 'Completado',
        'closed_with_loss' => 'Cerrado con pérdida',
        'cancelled' => 'Cancelado',
    ],
    'purchase_order_status' => [
        'draft' => 'Borrador',
        'ordered' => 'Pedido',
        'partially_received' => 'Recibido parcialmente',
        'received' => 'Recibido',
        'cancelled' => 'Cancelado',
    ],
    'sales_order_status' => [
        'draft' => 'Borrador',
        'confirmed' => 'Confirmado',
        'partially_dispatched' => 'Despachado parcialmente',
        'dispatched' => 'Despachado',
        'cancelled' => 'Cancelado',
    ],
    'stock_movement_type' => [
        'transfer_in' => 'Entrada por transferencia',
        'transfer_out' => 'Salida por transferencia',
        'purchase' => 'Compra',
        'purchase_return' => 'Devolución de compra',
        'sale' => 'Venta',
        'sale_return' => 'Devolución de venta',
        'adjustment' => 'Ajuste',
        'loss' => 'Pérdida',
        'damage' => 'Daño',
    ],
    'revision_status' => [
        'pending' => 'Pendiente',
        'accepted' => 'Aceptado',
        'rejected' => 'Rechazado',
    ],
    'negotiation_side' => [
        'requestor' => 'Solicitante',
        'fulfiller' => 'Proveedor interno',
    ],
    'in_transit_status' => [
        'in_transit' => 'En tránsito',
        'cleared' => 'Liberado',
        'lost' => 'Perdido',
    ],
    'loss_category' => [
        'shortfall' => 'Faltante',
        'damage' => 'Daño',
        'spoilage' => 'Deterioro',
        'theft' => 'Robo',
        'other' => 'Otro',
    ],
    'user_role' => [
        'admin' => 'Administrador',
        'auditor' => 'Auditor',
        'warehouse_staff' => 'Personal de almacén',
        'branch_manager' => 'Gerente de sucursal',
    ],
];
