<?php

declare(strict_types=1);

namespace Tests\Feature\ProductImport;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Import\ImportFileStore;
use App\Services\Import\ImportService;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

if (! function_exists('freezeProductImportPreview')) {
    /**
     * Freeze the preview exactly as the wizard's mapping step would: the
     * step's afterValidation hook does not run under callMountedAction, so
     * the modal tests pre-freeze preview.json next to the uploaded file.
     *
     * @param  array<string, string|int|null>  $columnMap
     */
    function freezeProductImportPreview(UploadedFile $file, string $definitionKey, array $columnMap, User $user): void
    {
        $preview = app(ImportService::class)->preview(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            $definitionKey,
            $columnMap,
            $user,
        );

        app(ImportFileStore::class)->writePreview((int) $user->getKey(), $preview);
    }
}

it('renders the import wizard action on ListProducts for authorized admins', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ListProducts::class)
        ->assertActionExists('importProducts', fn (Action $action): bool => $action->isWizard());
});

it('hides the import action from unauthorized warehouse staff', function () {
    $staff = User::factory()->warehouseStaff()->create();

    Livewire::actingAs($staff)
        ->test(ListProducts::class)
        ->assertActionHidden('importProducts');
});

it('freezes the preview server-side when the mapping step completes', function () {
    $admin = User::factory()->admin()->create();

    $csv = "Family Name,Category\nAeropress Roast,Equipment\n";
    $file = UploadedFile::fake()->createWithContent('families.csv', $csv);

    Livewire::actingAs($admin)
        ->test(ListProducts::class)
        ->mountAction('importProducts')
        ->setActionData([
            'mode' => 'products.families',
            'file' => $file,
            'columnMap' => ['name' => 'Family Name', 'category' => 'Category'],
        ])
        ->goToNextWizardStep()
        ->goToNextWizardStep();

    $preview = json_decode(
        Storage::disk('local')->get("imports/{$admin->getKey()}/preview.json"),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($preview['counts']['create'])->toBe(1)
        ->and($preview['counts']['total'])->toBe(1);
});

it('shows the mapping notice when the uploaded file can no longer be found', function () {
    $admin = User::factory()->admin()->create();

    $file = UploadedFile::fake()->createWithContent('products.csv', "Family Name,Category\nAeropress Roast,Equipment\n");

    $testable = Livewire::actingAs($admin)
        ->test(ListProducts::class)
        ->mountAction('importProducts')
        ->setActionData([
            'mode' => 'products.families',
            'file' => $file,
        ])
        ->goToNextWizardStep()
        ->setActionData([
            'mode' => 'products.families',
            'file' => null,
        ]);

    expect($testable->getMountedActionModalHtml())
        ->toContain(__('imports.errors.file_missing'));
});

it('executes a family import through the wizard modal', function () {
    $admin = User::factory()->admin()->create();

    $csv = "Family Name,Category\nAeropress Roast,Equipment\n";
    $file = UploadedFile::fake()->createWithContent('families.csv', $csv);
    $columnMap = ['name' => 'Family Name', 'category' => 'Category'];

    freezeProductImportPreview($file, 'products.families', $columnMap, $admin);

    $testable = Livewire::actingAs($admin)
        ->test(ListProducts::class)
        ->callAction('importProducts', data: [
            'mode' => 'products.families',
            'file' => $file,
            'columnMap' => $columnMap,
        ]);

    $testable->assertHasNoActionErrors();

    $this->assertDatabaseHas('products', [
        'name' => 'Aeropress Roast',
        'category' => 'Equipment',
    ]);
});

it('executes a confirmed variant update through the wizard modal', function () {
    $admin = User::factory()->admin()->create();

    $family = Product::factory()->create(['name' => 'Pour Over Beans']);
    $existing = ProductVariant::factory()->create([
        'product_id' => $family->id,
        'sku' => 'SKU-POUR-1',
        'name' => 'Old Pour Over',
    ]);

    $csv = "Family ID,SKU,Variant Name,Base Unit Name\n"
        ."{$family->id},SKU-POUR-1,New Pour Over,Bag\n";
    $file = UploadedFile::fake()->createWithContent('variants.csv', $csv);
    $columnMap = [
        'family_id' => 'Family ID',
        'sku' => 'SKU',
        'name' => 'Variant Name',
        'base_unit_name' => 'Base Unit Name',
    ];

    freezeProductImportPreview($file, 'products.variants', $columnMap, $admin);

    Livewire::actingAs($admin)
        ->test(ListProducts::class)
        ->callAction('importProducts', data: [
            'mode' => 'products.variants',
            'file' => $file,
            'columnMap' => $columnMap,
            'confirm_updates' => true,
        ])
        ->assertHasNoActionErrors();

    expect($existing->refresh()->name)->toBe('New Pour Over');
});

it('refreshes the preview and asks for a fresh confirmation when the database drifts', function () {
    $admin = User::factory()->admin()->create();

    $family = Product::factory()->create(['name' => 'Drift Beans']);
    ProductVariant::factory()->create([
        'product_id' => $family->id,
        'sku' => 'SKU-DRIFT-1',
        'name' => 'Drift Bag',
    ]);

    $csv = "Family ID,SKU,Variant Name,Base Unit Name\n{$family->id},SKU-DRIFT-1,Drift Bag,Bag\n";
    $file = UploadedFile::fake()->createWithContent('variants.csv', $csv);
    $columnMap = [
        'family_id' => 'Family ID',
        'sku' => 'SKU',
        'name' => 'Variant Name',
        'base_unit_name' => 'Base Unit Name',
    ];

    freezeProductImportPreview($file, 'products.variants', $columnMap, $admin);

    // Drift: the variant disappears between the preview and the run, so the
    // frozen "update 1" becomes "create 1" once the preview is re-derived.
    ProductVariant::where('sku', 'SKU-DRIFT-1')->first()->forceDelete();

    $testable = Livewire::actingAs($admin)
        ->test(ListProducts::class)
        ->callAction('importProducts', data: [
            'mode' => 'products.variants',
            'file' => $file,
            'columnMap' => $columnMap,
            'confirm_updates' => true,
        ]);

    $testable
        ->assertHasActionErrors(['confirm_updates'])
        ->assertActionDataSet(fn (array $state) => ['confirm_updates' => null]);

    // The preview is re-frozen from the live database instead of being left
    // at the stale counts the user just confirmed.
    expect(app(ImportFileStore::class)->readPreview((int) $admin->getKey())['counts'])
        ->toBe(['create' => 1, 'update' => 0, 'reject' => 0, 'total' => 1]);

    $this->assertDatabaseMissing('product_variants', ['sku' => 'SKU-DRIFT-1']);
});

it('refuses to import when no frozen preview exists', function () {
    $admin = User::factory()->admin()->create();

    $csv = "Family Name,Category\nAeropress Roast,Equipment\n";
    $file = UploadedFile::fake()->createWithContent('families.csv', $csv);

    Livewire::actingAs($admin)
        ->test(ListProducts::class)
        ->callAction('importProducts', data: [
            'mode' => 'products.families',
            'file' => $file,
            'columnMap' => ['name' => 'Family Name', 'category' => 'Category'],
        ])
        ->assertHasActionErrors(['confirm_updates']);

    $this->assertDatabaseMissing('products', ['name' => 'Aeropress Roast']);
});
