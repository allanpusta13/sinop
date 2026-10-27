<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * §0A.15 — TranslationKeyParityTest
 *
 * Cross-locale key parity against the canonical locale (`en`).
 *
 * Contract (blueprint §0A.15, line 436):
 *   - `en` is the canonical key set (source of truth).
 *   - Every configured locale MUST carry the same key set as `en`.
 *   - Intentionally-empty canonical namespaces (`forms`, `attributes`,
 *     `tables`, `widgets`) are valid; a locale that adds keys to one of
 *     those namespaces is an orphan and MUST be flagged, never dropped
 *     silently.
 *   - A value that resolves to its own raw dot-key (`'foo.bar' => 'foo.bar'`)
 *     is an untranslated key and MUST fail.
 *
 * Locales are enumerated from `lang/` (there is no `available_locales`
 * config key in this project). The canonical catalogue is loaded by
 * `require`-ing each file directly so a loader failure cannot pass silently.
 *
 * On failure the full divergence set is written to
 * `storage/logs/i18n-parity.md` and inlined (no truncation).
 *
 * This test enforces STRUCTURAL parity only — identical key sets across
 * locales. It does NOT enforce translation completeness: a value holding the
 * canonical English text tagged `// TODO(i18n): translate` is a valid
 * placeholder and is counted + reported but does NOT fail. Content
 * completeness is a separate workstream.
 */
function keyParityLocales(string $langDir): array
{
    $entries = scandir($langDir);

    if ($entries === false) {
        return [];
    }

    $locales = array_values(array_filter(
        $entries,
        fn (string $entry): bool => $entry !== '.' && $entry !== '..' && is_dir($langDir.DIRECTORY_SEPARATOR.$entry),
    ));

    sort($locales);

    return $locales;
}

/**
 * Flatten a nested lang array into dot-notation key => value.
 *
 * @param  array<int|string, mixed>  $array
 * @return array<string, mixed>
 */
function keyParityFlatten(array $array, string $prefix = ''): array
{
    $flat = [];

    foreach ($array as $key => $value) {
        $dotKey = $prefix === '' ? (string) $key : $prefix.'.'.$key;

        if (is_array($value)) {
            $flat += keyParityFlatten($value, $dotKey);

            continue;
        }

        $flat[$dotKey] = $value;
    }

    return $flat;
}

/**
 * Load a locale's catalogue as [namespace => [dotKey => value]].
 *
 * @return array<string, array<string, mixed>>
 */
function keyParityCatalogue(string $langDir, string $locale): array
{
    $files = glob($langDir.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.'*.php');

    if ($files === false) {
        return [];
    }

    sort($files);

    $catalogue = [];

    foreach ($files as $file) {
        $namespace = pathinfo($file, PATHINFO_FILENAME);
        $contents = require $file;

        if (! is_array($contents)) {
            continue;
        }

        $catalogue[$namespace] = keyParityFlatten($contents, $namespace);
    }

    return $catalogue;
}

/**
 * Count `// TODO(i18n): translate` placeholder occurrences in a locale's files.
 *
 * The parity test enforces STRUCTURAL parity — key sets must match. It does
 * NOT enforce translation completeness: a value carrying the canonical
 * English text tagged `// TODO(i18n): translate` counts as a placeholder and
 * is reported here but does NOT fail the test. Content completeness is a
 * separate workstream (see docs/01-issues/i18n-parity-orphans-{es,tl}.txt).
 *
 * @return array{total: int, files: array<string, int>}
 */
function keyParityPlaceholders(string $langDir, string $locale): array
{
    $files = glob($langDir.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.'*.php');

    if ($files === false) {
        return ['total' => 0, 'files' => []];
    }

    sort($files);

    $perFile = [];
    $total = 0;

    foreach ($files as $file) {
        $namespace = pathinfo($file, PATHINFO_FILENAME);

        if (! is_file($file)) {
            continue;
        }

        $count = mb_substr_count((string) file_get_contents($file), '// TODO(i18n): translate');

        if ($count > 0) {
            $perFile[$namespace] = $count;
        }

        $total += $count;
    }

    return ['total' => $total, 'files' => $perFile];
}

it('all_locales_match_canonical_key_set', function () {
    $langDir = base_path('lang');
    $canonical = 'en';

    $locales = keyParityLocales($langDir);

    expect($locales)->toContain($canonical);

    $canonicalCatalogue = keyParityCatalogue($langDir, $canonical);

    // failure buckets keyed "locale|namespace" => list of dot-keys
    $missing = [];
    $orphans = [];
    $identity = [];

    foreach ($locales as $locale) {
        if ($locale === $canonical) {
            continue;
        }

        $catalogue = keyParityCatalogue($langDir, $locale);

        $namespaces = array_values(array_unique(array_merge(
            array_keys($canonicalCatalogue),
            array_keys($catalogue),
        )));
        sort($namespaces);

        foreach ($namespaces as $namespace) {
            $canonicalKeys = array_keys($canonicalCatalogue[$namespace] ?? []);
            $localeKeys = array_keys($catalogue[$namespace] ?? []);

            // Both sides empty: intentionally-empty namespace on both ends — valid.
            if ($canonicalKeys === [] && $localeKeys === []) {
                continue;
            }

            foreach (array_diff($canonicalKeys, $localeKeys) as $key) {
                $missing["{$locale}|{$namespace}"][] = $key;
            }

            foreach (array_diff($localeKeys, $canonicalKeys) as $key) {
                $orphans["{$locale}|{$namespace}"][] = $key;
            }

            foreach ($catalogue[$namespace] ?? [] as $key => $value) {
                if (is_string($value) && $value === $key) {
                    $identity["{$locale}|{$namespace}"][] = $key;
                }
            }
        }
    }

    $count = static fn (array $buckets): int => array_sum(array_map('count', $buckets));

    $report = "# Translation key parity (§0A.15)\n\n";
    $report .= "Canonical locale: {$canonical}\n";
    $report .= 'Locales: '.implode(', ', $locales)."\n";
    $report .= 'Canonical keys: '.array_sum(array_map('count', $canonicalCatalogue))."\n\n";
    $report .= 'Missing keys: '.$count($missing)."\n";
    $report .= 'Orphan keys (no canonical equivalent): '.$count($orphans)."\n";
    $report .= 'Identity keys (value === key): '.$count($identity)."\n\n";

    // Placeholder counts — reported, NOT enforced. Structural parity is green
    // while placeholders remain; content completeness is a separate workstream.
    $placeholderCounts = [];

    foreach ($locales as $locale) {
        if ($locale === $canonical) {
            continue;
        }

        $placeholderCounts[$locale] = keyParityPlaceholders($langDir, $locale);
    }

    $report .= "## Placeholder Counts (Incomplete Translations)\n\n";
    $report .= "Values tagged `// TODO(i18n): translate` — English text standing in for a\n";
    $report .= "real translation. Does NOT fail the parity test.\n\n";

    foreach ($placeholderCounts as $locale => $data) {
        $report .= "- `{$locale}`: {$data['total']} placeholders across ".count($data['files'])." files\n";
    }

    $report .= "\n";

    foreach ($placeholderCounts as $locale => $data) {
        if ($data['files'] === []) {
            continue;
        }

        ksort($data['files']);
        $report .= "### {$locale} placeholders per file\n\n";

        foreach ($data['files'] as $namespace => $n) {
            $report .= "- `{$namespace}`: {$n}\n";
        }

        $report .= "\n";
    }

    foreach ([
        'MISSING (in canonical en, absent in locale)' => $missing,
        'ORPHAN (in locale, absent in canonical en)' => $orphans,
        'IDENTITY (value resolves to its own raw key)' => $identity,
    ] as $heading => $buckets) {
        $report .= "## {$heading}\n\n";

        if ($buckets === []) {
            $report .= "None.\n\n";

            continue;
        }

        ksort($buckets);

        foreach ($buckets as $bucket => $keys) {
            sort($keys);
            $report .= "- {$bucket} (".count($keys)."):\n";

            foreach ($keys as $key) {
                $report .= "  - `{$key}`\n";
            }
        }

        $report .= "\n";
    }

    File::ensureDirectoryExists(storage_path('logs'));
    File::put(storage_path('logs/i18n-parity.md'), $report);

    expect($missing)->toBeEmpty("Translation key parity FAILED (§0A.15).\nFull report: storage/logs/i18n-parity.md\n\n{$report}");

    // Zero-orphan enforcement: the live catalogue must carry no key absent from
    // canonical `en`. The `es`/`tl` retired-orphan sidecars
    // (docs/01-issues/i18n-parity-orphans-{es,tl}.txt) are a *disposition
    // record* of keys already removed from the live catalogue, not live keys —
    // they must never be re-added to satisfy this assertion.
    expect($orphans)->toBeEmpty("Translation key parity FAILED (§0A.15): orphan keys present with no canonical equivalent.\nFull report: storage/logs/i18n-parity.md\n\n{$report}");

    // Placeholder presence is now enforced at zero: the content-backfill
    // workstream (#172) has translated every `// TODO(i18n): translate` entry,
    // so any surviving marker is a regression, not migration state.
    foreach ($placeholderCounts as $locale => $data) {
        expect($data['total'])->toBe(0, "Expected {$locale} to carry zero untranslated placeholders; found {$data['total']}.");
    }
});
