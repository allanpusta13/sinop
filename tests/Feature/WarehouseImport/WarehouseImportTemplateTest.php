<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

test('admins can download the localized CSV template', function () {
    actingAsAdmin();

    $response = $this->get(route('imports.template', [
        'import' => 'warehouses',
        'format' => 'csv',
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();

    expect($content)->toContain(__('resources.warehouses.fields.code'))
        ->and($content)->toContain(__('resources.warehouses.fields.name'))
        ->and($content)->toContain(__('resources.warehouses.fields.location'))
        ->and($content)->toContain('WH-0001')
        ->and($content)->toContain('North Warehouse')
        ->and($content)->toContain('Main St. 123');
});

test('admins can download the XLSX template', function () {
    actingAsAdmin();

    $response = $this->get(route('imports.template', [
        'import' => 'warehouses',
        'format' => 'xlsx',
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('users the policy denies cannot download the template', function () {
    actingAsWarehouseStaff();

    $this->get(route('imports.template', [
        'import' => 'warehouses',
        'format' => 'csv',
    ]))->assertForbidden();
});
