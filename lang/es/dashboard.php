<?php

declare(strict_types=1);

return [
    'stats' => [
        'on_hand' => 'En existencia',
        'in_transit' => 'En tránsito',
        'pending' => 'Pendiente',
        'write_off' => 'Baja',
        'warehouse' => 'Almacén',
        'on_hand_description' => 'Total de unidades base en los almacenes del alcance.',
        'pending_description' => 'Requisiciones en espera de despacho o confirmación.',
        'in_transit_description' => 'Carga despachada aún no contabilizada en el destino.',
        'write_off_description' => 'Pérdida financiera total registrada.',
    ],
    'charts' => [
        'available' => 'Disponible',
        'dispatched' => 'Despachado',
        'net_movement' => 'Movimiento neto',
        'purchases' => 'Compras',
        'revenue' => 'Ingresos',
        'sales' => 'Ventas',
    ],
    'pending' => [
        'purchases' => 'Compras pendientes',
        'sales' => 'Ventas pendientes',
        'warehouse' => 'Almacén',
    ],
    'quick_actions' => [
        'new_transfer' => 'Nueva transferencia',
        'new_direct_transfer' => 'Nueva transferencia directa',
        'new_purchase' => 'Nueva compra',
        'new_sale' => 'Nueva venta',
    ],
    'active_in_transit' => [
        'qty' => 'Cantidad',
        'requisition' => 'Requisición',
        'sku' => 'SKU',
        'status' => 'Estado',
    ],
];
