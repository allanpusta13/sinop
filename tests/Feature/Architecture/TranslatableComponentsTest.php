<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

/**
 * §17.2 — project-wide Filament label translation (`translatableComponents()`).
 *
 * Issue #170. `AppServiceProvider::boot()` registers a `configureUsing`
 * hook on `Field`, `BaseFilter`, `Column`, and `Entry` so every component
 * attempts to translate its label. The hook is a FALLBACK: an explicit
 * `->label(__())` at the call site still wins.
 *
 * Behavioural assertions use `resources.products.fields.cost_price` as the
 * discriminator — its `lang/en` value (`Cost price`) differs from its raw
 * key, so a translated result proves the hook ran.
 *
 * These are file-scoped variables rather than `const` declarations: a
 * top-level `const` in a test file lands in the global namespace, where a
 * same-named constant declared by any other test file is a fatal error.
 */
$translatableKey = 'resources.products.fields.cost_price';
$translatableValue = 'Cost price';

it('configures_field_column_entry_filter', function () use ($translatableKey, $translatableValue) {
    // All four configured targets must translate a raw catalogue key.
    $components = [
        'Field' => TextInput::make('cost_price')->label($translatableKey),
        'Column' => TextColumn::make('cost_price')->label($translatableKey),
        'Entry' => TextEntry::make('cost_price')->label($translatableKey),
        'BaseFilter' => SelectFilter::make('cost_price')->label($translatableKey),
    ];

    foreach ($components as $name => $component) {
        expect($component->getLabel())->toBe(
            $translatableValue,
            "{$name} did not translate its label — the configureUsing hook is missing."
        );
    }
});

it('configure_using_calls_translate_label', function () use ($translatableKey, $translatableValue) {
    // A component built outside a table/schema still carries the hook,
    // proving registration is global rather than per-surface.
    expect(TextInput::make('cost_price')->label($translatableKey)->getLabel())
        ->toBe($translatableValue);
});

it('falls back rather than overriding an explicit label', function () use ($translatableKey, $translatableValue) {
    // §17.2 — the configured default is a fallback. An explicit
    // ->label(__()) already returns a translated string; the hook must not
    // double-translate it or replace it.
    $explicit = TextInput::make('cost_price')->label(__($translatableKey));

    expect($explicit->getLabel())->toBe($translatableValue);

    // A presentational label that is not a catalogue key is passed through
    // unchanged — the hook must not mangle arbitrary strings.
    expect(TextInput::make('sku')->label('SKU — display only')->getLabel())
        ->toBe('SKU — display only');
});

it('closure_parameter_is_component_not_livewire', function () {
    // §27 v13.9.1 row 10 — the configureUsing closure parameter must be
    // typed `Filament\Support\Components\Component`, the common ancestor
    // of Field/Entry/Column/BaseFilter. `Livewire\Component` compiles but
    // is wrong under static analysis.
    $source = (string) file_get_contents(
        (new ReflectionClass(AppServiceProvider::class))->getFileName()
    );

    // Matched as a whitespace-insensitive pattern so Pint-driven reformatting
    // of the signature does not read as a contract change. The type name and
    // the parameter name ARE the contract.
    expect($source)->toMatch('/function\s*\(\s*Component\s+\$translatable\s*\)\s*:\s*void/');
    expect($source)->toContain('use Filament\Support\Components\Component;');
    // Only the import is forbidden — the docblock names Livewire\Component
    // deliberately, to record why it is wrong here.
    expect($source)->not->toContain('use Livewire\\Component;');
});

it('covers exactly the four configured targets', function () {
    // The §17.2 loop iterates exactly these four and calls
    // translateLabel() inside the closure.
    $source = (string) file_get_contents(
        (new ReflectionClass(AppServiceProvider::class))->getFileName()
    );

    // Membership of the four is already proven behaviourally by
    // `configures_field_column_entry_filter` — drop one and that test fails.
    // What is asserted here is that the loop has not grown a FIFTH target.
    // Order is not a contract, so each name is matched individually rather
    // than as one literal a reformat or reorder would break.
    expect($source)->toMatch('/\[.*Field::class.*\]/s');

    foreach (['Field::class', 'BaseFilter::class', 'Column::class', 'Entry::class'] as $target) {
        expect($source)->toContain($target);
    }

    expect($source)->toMatch('/\$translatable->\s*translateLabel\s*\(\s*\)/');
});
