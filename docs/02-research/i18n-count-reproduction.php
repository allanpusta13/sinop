<?php

/**
 * Throwaway audit script — reproduces §0A.15 counts using the parity
 * test's own counting rule. Not part of the application.
 */
$langDir = __DIR__.'/../../lang';

function flatten(array $array, string $prefix = ''): array
{
    $flat = [];
    foreach ($array as $key => $value) {
        $dotKey = $prefix === '' ? (string) $key : $prefix.'.'.$key;
        if (is_array($value)) {
            $flat += flatten($value, $dotKey);

            continue;
        }
        $flat[$dotKey] = $value;
    }

    return $flat;
}

function catalogue(string $langDir, string $locale): array
{
    $files = glob($langDir.'/'.$locale.'/*.php');
    sort($files);
    $catalogue = [];
    foreach ($files as $file) {
        $ns = pathinfo($file, PATHINFO_FILENAME);
        $contents = require $file;
        if (! is_array($contents)) {
            continue;
        }
        $catalogue[$ns] = flatten($contents, $ns);
    }

    return $catalogue;
}

function placeholders(string $langDir, string $locale): array
{
    $files = glob($langDir.'/'.$locale.'/*.php');
    sort($files);
    $perFile = [];
    $total = 0;
    foreach ($files as $file) {
        $ns = pathinfo($file, PATHINFO_FILENAME);
        $count = mb_substr_count((string) file_get_contents($file), '// TODO(i18n): translate');
        if ($count > 0) {
            $perFile[$ns] = $count;
        }
        $total += $count;
    }

    return ['total' => $total, 'files' => $perFile];
}

$locales = array_values(array_filter(scandir($langDir), fn ($e) => $e !== '.' && $e !== '..' && is_dir($langDir.'/'.$e)));
sort($locales);

$en = catalogue($langDir, 'en');
echo 'Canonical (en) key count: '.array_sum(array_map('count', $en))."\n";
echo "Canonical (en) per namespace:\n";
foreach ($en as $ns => $keys) {
    printf("  %-20s %d\n", $ns, count($keys));
}

echo "\n=== PLACEHOLDER COUNTS (rule: mb_substr_count of '// TODO(i18n): translate') ===\n";
foreach ($locales as $locale) {
    if ($locale === 'en') {
        continue;
    }
    $p = placeholders($langDir, $locale);
    echo "{$locale}: total={$p['total']} across ".count($p['files'])." files\n";
    ksort($p['files']);
    foreach ($p['files'] as $ns => $n) {
        echo "    {$ns}: {$n}\n";
    }
}

echo "\n=== ORPHANS (locale keys absent from canonical en) ===\n";
foreach ($locales as $locale) {
    if ($locale === 'en') {
        continue;
    }
    $cat = catalogue($langDir, $locale);
    $orphans = [];
    $namespaces = array_unique(array_merge(array_keys($en), array_keys($cat)));
    sort($namespaces);
    foreach ($namespaces as $ns) {
        $enKeys = array_keys($en[$ns] ?? []);
        $loKeys = array_keys($cat[$ns] ?? []);
        if ($enKeys === [] && $loKeys === []) {
            continue;
        }
        foreach (array_diff($loKeys, $enKeys) as $key) {
            $orphans[$ns][] = $key;
        }
    }
    $total = array_sum(array_map('count', $orphans));
    echo "{$locale}: orphan total={$total}\n";
    ksort($orphans);
    foreach ($orphans as $ns => $keys) {
        echo "    {$ns}: ".count($keys)."\n";
    }
}
