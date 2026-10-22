<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/import_helpers.php';

use Tests\Feature\ImportEngine\Fixtures\ProductImportDefinitionFixture;

beforeEach(function () {
    registerFixtureImportDefinition();
});

test('admins can download the CSV template', function () {
    actingAsAdmin();

    $response = $this->get(route('imports.template', [
        'import' => ProductImportDefinitionFixture::KEY,
        'format' => 'csv',
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toContain('Family Name');
});

test('admins can download the XLSX template', function () {
    actingAsAdmin();

    $response = $this->get(route('imports.template', [
        'import' => ProductImportDefinitionFixture::KEY,
        'format' => 'xlsx',
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('non-admins are forbidden from downloading templates', function () {
    actingAsWarehouseStaff();

    $this->get(route('imports.template', [
        'import' => ProductImportDefinitionFixture::KEY,
        'format' => 'csv',
    ]))->assertForbidden();
});

test('turns away a user the panel no longer accepts', function () {
    // A live session alone is not enough: the panel re-runs
    // canAccessPanel(), so a deactivated account is refused its own
    // templates rather than handed them.
    actingAsAdmin(['is_active' => false]);

    $this->get(route('imports.template', [
        'import' => ProductImportDefinitionFixture::KEY,
        'format' => 'csv',
    ]))->assertForbidden();
});

test('guests are redirected to login', function () {
    actingAsGuest();

    $this->get(route('imports.template', [
        'import' => ProductImportDefinitionFixture::KEY,
        'format' => 'csv',
    ]))->assertRedirect();
});

test('unknown import keys 404', function () {
    actingAsAdmin();

    $this->get(route('imports.template', [
        'import' => 'fixture.missing',
        'format' => 'csv',
    ]))->assertNotFound();
});

test('unsupported formats 404', function () {
    actingAsAdmin();

    $this->get(route('imports.template', [
        'import' => ProductImportDefinitionFixture::KEY,
        'format' => 'pdf',
    ]))->assertNotFound();
});
