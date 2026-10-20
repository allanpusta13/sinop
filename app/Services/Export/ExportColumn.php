<?php

declare(strict_types=1);

namespace App\Services\Export;

/**
 * ExportColumn — one header/cell pair of an export definition.
 *
 * `key` must equal the SQL alias selected by the definition's query()
 * (the row object is read as `$row->{$key}`), `labelKey` is a lang key
 * resolved at render time, and `cast` normalizes the raw SQL value
 * before it reaches the writer: `string` (default), `int`, `float`,
 * or `datetime` (Y-m-d H:i:s).
 */
final class ExportColumn
{
    public const CAST_STRING = 'string';

    public const CAST_INT = 'int';

    public const CAST_FLOAT = 'float';

    public const CAST_DATETIME = 'datetime';

    public function __construct(
        public readonly string $key,
        public readonly string $labelKey,
        public readonly string $cast = self::CAST_STRING,
    ) {}

    /**
     * Resolved header label for this column.
     */
    public function label(): string
    {
        return __($this->labelKey);
    }
}
