<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * ImportDefinition — contract every import in the suite implements.
 *
 * The shared engine handles parsing, header mapping, private file
 * storage, frozen previews, confirmation, queueing and transactional
 * execution. A definition supplies only what is feature-specific: its
 * columns, its server-side authorization, its single validation pass
 * (shared by preview and execution) and its persistence step.
 */
abstract class ImportDefinition
{
    /** Registry key, e.g. "products.families". */
    abstract public function key(): string;

    /** Translation key of the definition display name (mode selector / errors). */
    abstract public function labelKey(): string;

    /**
     * The importable columns, in template order.
     *
     * @return array<int, ImportColumn>
     */
    abstract public function columns(): array;

    /**
     * Server-side authorization. Called at preview, at confirm and
     * inside the queued job. Must throw AuthorizationException when
     * denied; the default `can()` helper wraps this for visibility
     * gates.
     */
    abstract public function authorize(User $user): void;

    /**
     * Row classification — the single source of truth shared by preview
     * and execution. Each input row is
     * `['row_number' => int, 'data' => array<string, mixed>]` where
     * only mapped columns are present in `data` (an omitted key means
     * the column was not mapped, which is distinct from an empty cell).
     *
     * @param  array<int, array{row_number: int, data: array<string, mixed>}>  $rows
     * @return array{rows: array<int, array<string, mixed>>, counts: array{create: int, update: int, reject: int, total: int}}
     */
    abstract public function validate(array $rows): array;

    /**
     * Apply the rows classified `create` or `update` (reject rows are
     * counted by the engine and never passed in). Called inside the
     * engine transaction.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{created: int, updated: int, skipped: int}
     */
    abstract public function persist(array $rows): array;

    /**
     * Mapping gate: every required column must be mapped before the
     * preview may be built. Definitions with "at least one of" mapping
     * groups override this and call the parent first.
     *
     * @param  array<string, string|int|null>  $columnMap
     */
    public function assertMappingsSatisfied(array $columnMap): void
    {
        foreach ($this->columns() as $column) {
            if ($column->required && blank($columnMap[$column->key] ?? null)) {
                throw ValidationException::withMessages([
                    "columnMap.{$column->key}" => __('imports.errors.missing_required_mapping', [
                        'field' => $column->label(),
                    ]),
                ]);
            }
        }
    }

    public function label(): string
    {
        return __($this->labelKey());
    }

    public function description(): string
    {
        return __($this->labelKey().'.description');
    }

    public function can(User $user): bool
    {
        try {
            $this->authorize($user);

            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }
}
