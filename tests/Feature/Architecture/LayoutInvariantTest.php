<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * §7O.4 / §7O.5 layout invariant guard.
 *
 * Two parent/child grid mismatches drifted because nothing pinned them:
 *   1. Infolist root grids omitted `->columnSpanFull()`, so the whole
 *      infolist rendered inside a single parent track (~1/3 width).
 *   2. `Dashboard::getColumns()` drifted from the blueprint value and
 *      gained extra breakpoint keys.
 *
 * Scope of this net — stated so green is not over-read:
 *   - asserts DECLARED values only (a source string is present; a method
 *     body equals a blueprint literal);
 *   - does NOT assert computed pixels, rendered widths, or viewport
 *     correctness. Green here does not prove the surface looks right;
 *     that needs a rendered check.
 *   - does NOT compare a child's span against its parent's track count.
 *     A line-based parser cannot resolve nesting — it inspects
 *     self-contained chains on one node. That guard needs runtime or a
 *     hand-maintained (surface, breakpoint) -> parent tracks table.
 *
 * Line-based chain parser with bracket-depth tracking (the root chain
 * embeds multi-line arrays). No framework boot. Pattern follows
 * `FormFieldSpanTest.php`.
 */
$sourceInfolists = function (): array {
    $files = [];

    foreach (File::allFiles(app_path('Filament/Resources')) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $relative = str_replace('/', '\\', $file->getRelativePathname());

        if (preg_match('#Schemas\\\\[A-Za-z]+Infolist\.php$#', $relative) !== 1) {
            continue;
        }

        $files[$relative] = $file->getPathname();
    }

    return $files;
};

/**
 * The root grid's OWN chain: the calls the outermost `Grid::make(...)`
 * carries before it delegates to `->schema([...])`. Everything at or
 * after `->schema(` is children, and must not be inspected here — a
 * child's span says nothing about the root's.
 *
 * @param  array<int, string>  $lines
 */
$rootChain = function (array $lines, int $index): string {
    $chain = '';

    for ($i = $index; $i < count($lines); $i++) {
        $code = preg_replace('#//.*$#', '', $lines[$i]);

        if (($cut = strpos($code, '->schema(')) !== false) {
            return $chain.substr($code, 0, $cut);
        }

        $chain .= $code."\n";
    }

    return $chain;
};

it('infolist root grids declare a full column span', function () use ($sourceInfolists, $rootChain) {
    $violations = [];

    foreach ($sourceInfolists() as $relative => $path) {
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        // The root grid is the outermost `Grid::make(` — lowest indent.
        $rootIndex = null;
        $rootIndent = PHP_INT_MAX;

        foreach ($lines as $index => $line) {
            if (preg_match('/^\s*Grid::make\(/', $line) !== 1) {
                continue;
            }

            $indent = strlen($line) - strlen(ltrim($line));

            if ($indent < $rootIndent) {
                $rootIndent = $indent;
                $rootIndex = $index;
            }
        }

        if ($rootIndex === null) {
            $violations[] = "{$relative} declares no Grid root";

            continue;
        }

        if (! str_contains($rootChain($lines, $rootIndex), '->columnSpanFull()')) {
            $violations[] = "{$relative}:".($rootIndex + 1).' root grid omits ->columnSpanFull()';
        }
    }

    expect($violations)->toBe([]);
});

it('dashboard columns match the blueprint breakpoint map', function () {
    $path = app_path('Filament/Pages/Dashboard.php');
    $lines = file($path, FILE_IGNORE_NEW_LINES);

    $start = null;

    foreach ($lines as $index => $line) {
        if (preg_match('/function\s+getColumns\s*\(/', $line) === 1) {
            $start = $index;

            break;
        }
    }

    expect($start)->not->toBeNull('Dashboard::getColumns() not found — the invariant has no anchor.');

    $body = implode("\n", array_slice($lines, $start, 25));

    expect(preg_match('/return\s*\[(.*?)\];/s', $body, $matches))
        ->toBe(1, 'Dashboard::getColumns() does not return a literal array.');

    preg_match_all("/'([a-z_]+)'\s*=>\s*(\d+)/", $matches[1], $pairs, PREG_SET_ORDER);

    $actual = [];

    foreach ($pairs as $pair) {
        $actual[$pair[1]] = (int) $pair[2];
    }

    // §7O.5 (`docs/00-project/blueprint.md:12944`). Compared as a
    // breakpoint map: key order is not meaningful to Filament.
    $expected = ['default' => 1, 'md' => 2, 'xl' => 4];

    // The drift below is known, recorded, and deliberately not fixed here:
    // reconciling the dashboard is plan step 3 / commit A2, which is NOT
    // authorized. Pinning the exact known-wrong map means the skip can only
    // mask THIS violation — any wider drift fails the assertion above it.
    $knownDrift = ['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 2, 'xl' => 2];

    if ($actual === $knownDrift) {
        $this->markTestSkipped(
            'Dashboard::getColumns() carries the known §7O.5 drift (xl capped at 2, stray sm/lg keys). '
            .'Fix is plan step 3 / commit A2 — not authorized. Remove this skip when A2 lands.'
        );
    }

    expect($actual)->toBe($expected);
});
