<?php

declare(strict_types=1);

require_once __DIR__.'/Fixtures/import_helpers.php';

use App\Exceptions\DomainRuleViolationException;
use App\Jobs\ProcessImportJob;
use App\Models\User;
use App\Services\Import\ImportFileStore;
use App\Services\Import\ImportParser;
use App\Services\Import\ImportService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\Feature\ImportEngine\Fixtures\FixtureImportWizardAction;

beforeEach(function () {
    registerFixtureImportDefinition();
    Storage::fake('local');
    actingAsAdmin();
});

if (! function_exists('importWizardGetStub')) {
    /** Array-backed Get stub: reads state from a plain array. */
    function importWizardGetStub(array $state): Get
    {
        return new class($state) extends Get
        {
            public function __construct(private array $stubState) {}

            public function __invoke(string|Component $path = '', bool $isAbsolute = false): mixed
            {
                return data_get($this->stubState, $path);
            }
        };
    }
}

if (! function_exists('importWizardSetStub')) {
    /** Array-backed Set stub: records writes instead of touching a schema. */
    function importWizardSetStub(): Set
    {
        return new class extends Set
        {
            /** @var array<string, mixed> */
            public array $written = [];

            public function __construct() {}

            public function __invoke(string|Component $path, mixed $state, bool $isAbsolute = false, bool $shouldCallUpdatedHooks = false): mixed
            {
                $this->written[(string) $path] = $state;

                return $state;
            }
        };
    }
}

test('builds a three-step wizard action for its definition', function () {
    $action = FixtureImportWizardAction::make();

    expect($action)->toBeInstanceOf(Action::class)
        ->and($action->isWizard())->toBeTrue()
        ->and($action->getName())->toBe('fixtureImport');
});

test('re-seeds the mapping selects with guesses when the file or mode changes', function () {
    $userId = (int) auth()->id();
    $disk = Storage::disk('local');
    $disk->put("imports/{$userId}/file.csv", "Name,Category\nArabica,Beverages\n");

    $action = new FixtureImportWizardAction;

    $get = importWizardGetStub(['mode' => null, 'file' => "imports/{$userId}/file.csv"]);
    $set = importWizardSetStub();

    callProtected($action, 'applyMappingGuesses', [$get, $set]);

    expect($set->written)->toBe([
        'columnMap.name' => 'Name',
        'columnMap.category' => 'Category',
    ]);
});

test('caps the frozen preview at the display row limit, keeping exact counts', function () {
    $userId = (int) auth()->id();
    $action = new FixtureImportWizardAction;

    $rows = [];

    for ($i = 1; $i <= 101; $i++) {
        $rows[] = ['row_number' => $i, 'status' => 'create', 'identifier' => "Product {$i}", 'errors' => []];
    }

    callProtected($action, 'storePreview', [$userId, [
        'rows' => $rows,
        'counts' => ['create' => 101, 'update' => 0, 'reject' => 0, 'total' => 101],
        'has_updates' => false,
    ]]);

    $stored = app(ImportFileStore::class)->readPreview($userId);

    // Exactly PREVIEW_ROW_LIMIT rows are kept for display...
    expect($stored['rows'])->toHaveCount(100)
        ->and($stored['rows_truncated'])->toBeTrue()
        ->and($stored['counts']['create'])->toBe(101)
        ->and($stored['counts']['total'])->toBe(101);
});

test('stores a preview under the row limit untouched', function () {
    $userId = (int) auth()->id();
    $action = new FixtureImportWizardAction;

    callProtected($action, 'storePreview', [$userId, [
        'rows' => [['row_number' => 1, 'status' => 'create', 'identifier' => 'A', 'errors' => []]],
        'counts' => ['create' => 1, 'update' => 0, 'reject' => 0, 'total' => 1],
        'has_updates' => false,
    ]]);

    $stored = app(ImportFileStore::class)->readPreview($userId);

    expect(array_key_exists('rows_truncated', $stored))->toBeFalse()
        ->and($stored['rows'])->toHaveCount(1);
});

test('freezes a translated reject row when the file is missing', function () {
    $action = new FixtureImportWizardAction;

    callProtected($action, 'freezePreview', [importWizardGetStub(['file' => null, 'mode' => null, 'columnMap' => []])]);

    $stored = app(ImportFileStore::class)->readPreview((int) auth()->id());

    expect($stored['rows'][0]['status'])->toBe('reject')
        ->and($stored['rows'][0]['errors'])->toBe([__('imports.errors.file_missing')])
        ->and($stored['counts'])->toBe(['create' => 0, 'update' => 0, 'reject' => 1, 'total' => 1])
        ->and($stored['fingerprint'])->toBeString();
});

test('freezes a translated domain failure as the reject row', function () {
    $userId = (int) auth()->id();
    Storage::disk('local')->put("imports/{$userId}/file.csv", "Name,Category\nArabica,Beverages\n");

    $context = ['expected_create' => 3, 'expected_update' => 0, 'expected_reject' => 0, 'actual_create' => 1, 'actual_update' => 2, 'actual_reject' => 0];

    app()->instance(ImportService::class, new class(app(ImportParser::class), app(ImportFileStore::class)) extends ImportService
    {
        public function preview(string $filePath, ?string $originalName, string $definitionKey, array $columnMap, User $user): array
        {
            throw new DomainRuleViolationException('errors.import_counts_mismatch', ['expected_create' => 3, 'expected_update' => 0, 'expected_reject' => 0, 'actual_create' => 1, 'actual_update' => 2, 'actual_reject' => 0]);
        }
    });

    $action = new FixtureImportWizardAction;

    callProtected($action, 'freezePreview', [importWizardGetStub(['file' => "imports/{$userId}/file.csv", 'mode' => null, 'columnMap' => []])]);

    $stored = app(ImportFileStore::class)->readPreview((int) auth()->id());

    expect($stored['rows'][0]['status'])->toBe('reject')
        ->and($stored['rows'][0]['errors'])->toBe([__('errors.import_counts_mismatch.body', $context)]);
});

test('freezes a generic message instead of leaking an unexpected failure', function () {
    $userId = (int) auth()->id();
    Storage::disk('local')->put("imports/{$userId}/file.csv", "Name,Category\nArabica,Beverages\n");

    app()->instance(ImportService::class, new class(app(ImportParser::class), app(ImportFileStore::class)) extends ImportService
    {
        public function preview(string $filePath, ?string $originalName, string $definitionKey, array $columnMap, User $user): array
        {
            throw new LogicException('raw internal detail that must not leak');
        }
    });

    $action = new FixtureImportWizardAction;

    callProtected($action, 'freezePreview', [importWizardGetStub(['file' => "imports/{$userId}/file.csv", 'mode' => null, 'columnMap' => []])]);

    $stored = app(ImportFileStore::class)->readPreview((int) auth()->id());

    expect($stored['rows'][0]['status'])->toBe('reject')
        ->and($stored['rows'][0]['errors'])->toBe([__('imports.errors.preview_failed')])
        ->and($stored['rows'][0]['errors'][0])->not->toContain('raw internal detail');
});

test('runImport queues an over-threshold import, notifies and keeps the staged file', function () {
    Queue::fake();

    $user = auth()->user();
    $disk = Storage::disk('local');

    $content = "Name,Category\n".implode("\n", array_map(
        static fn (int $i): string => "Product {$i},Beverages",
        range(1, 101),
    ))."\n";

    $sourcePath = "imports/{$user->getKey()}/source.csv";
    $disk->put($sourcePath, $content);
    // realpath, exactly like resolvePhysicalPath() will resolve it at submit.
    $source = realpath($disk->path($sourcePath));
    $columnMap = ['name' => 'Name', 'category' => 'Category'];

    $action = new FixtureImportWizardAction;

    $preview = app(ImportService::class)->preview($source, 'source.csv', 'fixture.products', $columnMap, $user);
    $preview['fingerprint'] = callProtected($action, 'previewFingerprint', [$source, 'fixture.products', $columnMap]);

    app(ImportFileStore::class)->writePreview((int) $user->getKey(), $preview);

    callProtected($action, 'runImport', [[
        'mode' => null,
        'file' => "imports/{$user->getKey()}/source.csv",
        'columnMap' => $columnMap,
        'confirm_updates' => false,
    ], new Schema]);

    Queue::assertPushed(
        ProcessImportJob::class,
        fn (ProcessImportJob $job): bool => $job->userId === (int) $user->getKey()
            && $job->expectedCounts['create'] === 101,
    );

    Notification::assertNotified(__('imports.notifications.queued_title'));

    // The queued job reads this file later — the wizard must keep it
    // and the frozen counts it confirmed.
    expect($disk->exists("imports/{$user->getKey()}/source.csv"))->toBeTrue()
        ->and($disk->exists("imports/{$user->getKey()}/preview.json"))->toBeTrue();
});

test('runImport refuses counts the user never confirmed', function () {
    $user = auth()->user();
    $disk = Storage::disk('local');

    $sourcePath = "imports/{$user->getKey()}/source.csv";
    $disk->put($sourcePath, "Name,Category\nRobusta,Beverages\n");
    $source = realpath($disk->path($sourcePath));
    $columnMap = ['name' => 'Name', 'category' => 'Category'];

    $action = new FixtureImportWizardAction;

    // Frozen for a different mapping (e.g. the mode changed after the
    // preview) — the fingerprint no longer matches, so submitting must
    // fall into the preview_expired refresh instead of importing.
    $preview = app(ImportService::class)->preview($source, 'source.csv', 'fixture.products', ['name' => 'Name'], $user);
    $preview['fingerprint'] = callProtected($action, 'previewFingerprint', [$source, 'fixture.products', ['name' => 'Name']]);

    app(ImportFileStore::class)->writePreview((int) $user->getKey(), $preview);

    try {
        callProtected($action, 'runImport', [[
            'mode' => null,
            'file' => "imports/{$user->getKey()}/source.csv",
            'columnMap' => $columnMap,
            'confirm_updates' => false,
        ], new Schema]);

        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $exception) {
        expect(collect($exception->errors())->flatten()->first())->toBe(__('imports.errors.preview_expired'))
            ->and($disk->exists("imports/{$user->getKey()}/source.csv"))->toBeTrue();
    }
});
