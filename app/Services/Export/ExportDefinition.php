<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Models\User;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;

/**
 * ExportDefinition — the contract every export type implements.
 *
 * One definition = one exportable type (#225 master data / #226
 * transactions and stock ledger). The engine owns formatting, paging,
 * authorization and download wiring; a definition only declares what
 * the file contains, how the rows are selected, and who may export it.
 *
 * Definitions are registered in `config/exports.php` and resolved by
 * ExportService::resolveDefinition(). Queries MUST be explicit select
 * lists with aliased columns — never `select *` — so that enum casts,
 * images and spec-silent fields can never leak into a file.
 */
abstract class ExportDefinition
{
    /**
     * Registry key, e.g. "products.variants". Dots are used in the
     * filename and in translation lookups.
     */
    abstract public function key(): string;

    /**
     * Lang key for the export type label shown in the action's type
     * selector and in the completion notification.
     */
    abstract public function labelKey(): string;

    /**
     * Model class the export authorizes against (child exports
     * authorize against their parent's model).
     *
     * @return class-string<Authorizable>
     */
    abstract public function modelClass(): string;

    /**
     * Server-side authorization. Called when the export is requested
     * and again inside the queued job. Must throw AuthorizationException
     * when denied; the default `can()` helper wraps this for visibility
     * gates.
     */
    abstract public function authorize(User $user): void;

    /**
     * Columns exported, in order. Each key must be an alias selected by
     * query().
     *
     * @return array<int, ExportColumn>
     */
    abstract public function columns(): array;

    /**
     * Shape the table query (already scoped, filtered, searched and
     * sorted by the List page) into the export's explicit select list,
     * adding joins where a column needs them. Must not add a limit.
     */
    abstract public function query(Builder $tableQuery): Builder;

    /**
     * Columns that place a row inside a warehouse, as OR-groups: a row
     * is in scope when *every* group holds at least one column
     * containing a warehouse the user is assigned to.
     *
     * One group of one column is the ordinary `warehouse_id in (…)` of
     * the ledger and order tables. One group of two columns is the
     * "either endpoint" shape of a transfer requisition, and two
     * single-column groups the stricter "both endpoints" shape of a
     * direct transfer (§20.1) — the grouping, not the engine, is what
     * says which.
     *
     * Empty when the export carries no warehouse scope at all (master
     * data): there is nothing to narrow, and the snapshot already
     * bounds the file to what the user was looking at.
     *
     * Read only on the queued path, where the export re-narrows its
     * dispatch-time snapshot against the warehouses the user holds
     * *now* — so a scope may only shrink while a job waits, never
     * grow.
     *
     * @return array<int, array<int, string>>
     */
    public function warehouseScope(): array
    {
        return [];
    }

    /** Default `can()` gate used by the action's ->authorize() visibility. */
    public function can(User $user): bool
    {
        try {
            $this->authorize($user);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    /**
     * The List page's query narrowed to its primary key, for use as the
     * scope subquery of a child export: the children of whatever the
     * user is looking at, never the whole table. Global scopes travel
     * with the subquery, and ordering is dropped so it stays a plain
     * IN() list.
     */
    protected function parentIdsQuery(Builder $tableQuery): Builder
    {
        return $tableQuery->clone()
            ->select($tableQuery->getModel()->getQualifiedKeyName())
            ->reorder();
    }
}
