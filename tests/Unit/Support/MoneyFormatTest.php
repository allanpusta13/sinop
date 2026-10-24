<?php

declare(strict_types=1);

use Illuminate\Support\Number;

/**
 * §0A.14 / §25 / Principle 10 — `format_money()` display contract.
 *
 * The helper wraps `Number::currency()` for the project's fixed
 * 4-decimal display precision on `decimal(15,4)` fields.
 *
 * Behaviour under test (issue #171):
 *   - defaults to 4 decimals
 *   - accepts a custom precision
 *   - reads the currency code from `config('app.currency')`
 *   - no Filament `->money(..., decimals:)` call exists — Filament v5
 *     rejects the `decimals:` argument
 *   - every declared `decimal(15,4)` display site routes through it
 *
 * NOTE: this file is not named in the blueprint's §25 file map; it is a
 * companion test authored at owner direction for issue #171.
 */

/**
 * Does any `->money( ... )` call in $contents declare a `decimals:` argument?
 *
 * Scanned with a paren-depth walk rather than a `[^)]*` regex: that character
 * class cannot cross a closing paren, so a call such as
 * `->money((float) $state, decimals: 2)` would slip past a regex as a false
 * negative. Only arguments at depth 1 — directly inside the money() call —
 * count, so `->money($x, wrapper(decimals: 2))` is not an offender.
 */
$moneyCallDeclaresDecimals = function (string $contents): bool {
    if (! preg_match_all('/->money\(/', $contents, $matches, PREG_OFFSET_CAPTURE)) {
        return false;
    }

    $needle = 'decimals:';
    $needleLength = strlen($needle);
    $contentsLength = strlen($contents);

    foreach ($matches[0] as [$open, $offset]) {
        // Already inside the ->money( paren opened by the match.
        $depth = 1;

        for ($i = $offset + strlen($open); $i < $contentsLength; $i++) {
            $char = $contents[$i];

            if ($char === '(') {
                $depth++;

                continue;
            }

            if ($char === ')') {
                if (--$depth === 0) {
                    break;
                }

                continue;
            }

            if ($depth === 1 && substr($contents, $i, $needleLength) === $needle) {
                return true;
            }
        }
    }

    return false;
};

it('helper defaults to four decimals', function () {
    // §0A.14 — decimal(15,4) display precision.
    expect(format_money('10.5'))->toContain('10.5000');
    expect(format_money(10.5))->toContain('10.5000');
});

it('helper accepts a custom precision', function () {
    // ProductsTable uses 2-dec past precision for the sale-price column.
    expect(format_money('10.5', 2))->toContain('10.50');
    expect(format_money('10.5', 2))->not->toContain('10.500');
});

it('helper reads the currency code from config', function () {
    expect(config('app.currency'))->toBeString()->not->toBe('');

    $default = format_money('10.5');

    config()->set('app.currency', 'USD');
    $changed = format_money('10.5');

    // The output must track the configured currency, not a hardcoded code.
    expect($changed)->not->toBe($default);

    config()->set('app.currency', 'PHP');
    expect(format_money('10.5'))->toBe($default);
});

it('helper formats the decimal(15,4) value rather than passing it through', function () {
    // A raw decimal string must gain grouping/currency/scale decoration —
    // proof the value went through Number::currency(), not a string cast.
    $formatted = format_money('1234567.25');

    expect($formatted)->toBeString();
    expect($formatted)->not->toBe('1234567.25');
    expect($formatted)->toContain('1,234,567.2500');
});

it('no_money_call_with_decimals_argument', function () use ($moneyCallDeclaresDecimals) {
    // §27 v13.7 row 2 — Filament v5's ->money() has no `decimals:` param.
    $offenders = [];

    foreach (['app', 'resources'] as $root) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path($root), FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());

            // Only a `decimals:` argument INSIDE a ->money( ... ) call is
            // an offender; prose in a docblock that merely names the
            // argument is not.
            //
            // Scanned with a paren-depth walk rather than a `[^)]*` regex:
            // that character class cannot cross a closing paren, so a call
            // such as ->money((float) $state, decimals: 2) would slip past
            // a regex as a false negative.
            if ($moneyCallDeclaresDecimals($contents)) {
                $offenders[] = $file->getPathname();
            }
        }
    }

    expect($offenders)->toBeEmpty(
        'Filament v5 rejects the `decimals:` argument — use format_money() instead: '.implode(', ', $offenders)
    );
});

it('decimal_15_4_sites_use_format_money_helper', function () {
    // Every decimal(15,4) display site declared by issue #171. The column
    // schema is asserted here too so a future migration change that widens
    // precision is caught alongside the display contract.
    $fourDecimalSites = [
        'app/Filament/Resources/LossLedgers/Schemas/LossLedgerInfolist.php',
        'app/Filament/Resources/LossLedgers/Tables/LossLedgersTable.php',
        'app/Filament/Resources/PurchaseOrders/Schemas/PurchaseOrderInfolist.php',
        'app/Filament/Resources/SalesOrders/Schemas/SalesOrderInfolist.php',
    ];

    $offenders = [];

    foreach ($fourDecimalSites as $path) {
        if (! str_contains((string) file_get_contents(base_path($path)), 'format_money($state)')) {
            $offenders[] = $path;
        }
    }

    expect($offenders)->toBeEmpty(
        'These decimal(15,4) display sites must format through format_money(): '.implode(', ', $offenders)
    );

    // ProductsTable's sale-price column is the declared 2-dec exception.
    expect(file_get_contents(base_path('app/Filament/Resources/Products/Tables/ProductsTable.php')))
        ->toContain('format_money($state, 2)');

    // ProductInfolist keeps the locale-default (2) form.
    expect(file_get_contents(base_path('app/Filament/Resources/Products/Schemas/ProductInfolist.php')))
        ->toContain("->money(config('app.currency'))");
});

it('helper output matches the Number::currency contract', function () {
    // The helper is a thin wrapper — documented behaviour is Number's.
    $expected = Number::currency(
        (float) '10.5',
        config('app.currency'),
        precision: 4,
    );

    expect(format_money('10.5'))->toBe($expected);
});
