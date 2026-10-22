<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * ImportColumn — value object describing one importable column: its
 * field key, the translation key of its header/label, whether mapping
 * it is mandatory, header-guessing keywords, the sample value printed
 * on templates, and the translation key of its short description.
 */
class ImportColumn
{
    /**
     * @param  array<int, string>  $guessKeywords
     */
    public function __construct(
        public readonly string $key,
        public readonly string $labelKey,
        public readonly bool $required = false,
        public readonly array $guessKeywords = [],
        public readonly string $sample = '',
        public readonly string $descriptionKey = '',
    ) {}

    public function label(): string
    {
        return __($this->labelKey);
    }

    public function description(): string
    {
        return filled($this->descriptionKey) ? __($this->descriptionKey) : $this->label();
    }
}
