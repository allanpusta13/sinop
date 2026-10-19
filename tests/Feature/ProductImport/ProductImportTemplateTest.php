<?php

declare(strict_types=1);

namespace Tests\Feature\ProductImport;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves the engine CSV template for the products.families definition', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->get(route('imports.template', ['import' => 'products.families', 'format' => 'csv']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();

    expect($content)->toContain('Family Name')
        ->and($content)->toContain('Family Category (optional)')
        ->and($content)->toContain('Arabica Specialty Coffee (Sample)')
        ->and($content)->not->toContain('is_active')
        ->and($content)->not->toContain('images');
});

it('serves the engine XLSX template for the products.families definition', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->get(route('imports.template', ['import' => 'products.families', 'format' => 'xlsx']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('serves the engine CSV template for the products.variants definition', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->get(route('imports.template', ['import' => 'products.variants', 'format' => 'csv']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();

    expect($content)->toContain('Family ID (optional)')
        ->and($content)->toContain('Family Name')
        ->and($content)->toContain('SKU')
        ->and($content)->toContain('Variant Name')
        ->and($content)->toContain('Base Unit Name')
        ->and($content)->toContain('Barcode (optional)')
        ->and($content)->toContain('Reorder Point (optional)')
        ->and($content)->toContain('Attributes (optional)')
        ->and($content)->toContain('Active Status (optional)')
        ->and($content)->toContain('SKU-0001-AA (Sample)')
        ->and($content)->not->toContain('Product.is_active')
        ->and($content)->not->toContain('images');
});

it('redirects unauthenticated users attempting to download templates to login', function () {
    auth()->logout();

    $response = $this->get(route('imports.template', ['import' => 'products.families', 'format' => 'csv']));

    $response->assertRedirect(route('filament.admin.auth.login'));
});

it('denies non-admin users from the engine template route with 403', function () {
    $staff = User::factory()->warehouseStaff()->create();

    $response = $this->actingAs($staff)
        ->get(route('imports.template', ['import' => 'products.variants', 'format' => 'csv']));

    $response->assertStatus(403);
});
