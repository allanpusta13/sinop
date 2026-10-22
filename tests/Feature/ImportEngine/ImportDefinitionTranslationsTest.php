<?php

declare(strict_types=1);

use Illuminate\Support\Facades\App;

test('resolves a translated label and description for every import in every locale', function () {
    $definitions = config('imports.definitions');

    expect($definitions)->not->toBeEmpty();

    foreach (['en', 'es', 'tl'] as $locale) {
        App::setLocale($locale);

        foreach ($definitions as $key => $class) {
            $definition = app($class);

            // description() is built by concatenating '_desc' onto
            // labelKey(), so a literal key scan never sees it — a
            // missing suffix key surfaces here as the raw key itself.
            $labelKey = $definition->labelKey();
            $descriptionKey = $labelKey.'_desc';

            $label = $definition->label();
            $description = $definition->description();

            expect(is_string($label))->toBeTrue("[$locale] [$key] label is not a string.");
            expect($label)->not->toBe($labelKey, "[$locale] [$key] label key never resolves.");
            expect(trim($label))->not->toBe('', "[$locale] [$key] label is blank.");

            expect(is_string($description))->toBeTrue("[$locale] [$key] description is not a string.");
            expect($description)->not->toBe($descriptionKey, "[$locale] [$key] description key never resolves.");
            expect(trim($description))->not->toBe('', "[$locale] [$key] description is blank.");
        }
    }

    App::setLocale('en');
});
