<?php

declare(strict_types=1);

return [
    'print' => [
        'title' => 'Nota de envío :ref',
        'heading' => 'Nota de envío',
        'route' => ':from → :to',
        'status' => 'Estado: :status',
        'variant' => 'Variante',
        'sku' => 'SKU',
        'qty' => 'Cantidad',
        'print_button' => 'Imprimir',
    ],
    'scan' => [
        'title' => 'Recepción por escaneo :ref',
        'heading' => 'Recepción por escaneo',
        'route' => ':from → :to',
        'sku' => 'SKU',
        'received_good' => 'Recibido (bueno)',
        'received_damaged' => 'Recibido (dañado)',
        'submit' => 'Enviar escaneo',
    ],
];
