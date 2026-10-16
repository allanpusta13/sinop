<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * User role — the single source of role-based authorization decisions.
 *
 * Blueprint §4.8 originally defined three cases (Admin, Auditor,
 * WarehouseStaff). The project now defines four; BranchManager has been
 * added per owner direction. This is a blueprint deviation — every
 * downstream section that enumerates roles (badge scope §1B.1a, policy
 * predicates §8, the `User` model §3.18, and `enums.user_role.*`
 * translations §0A.2a) must be extended to cover the new case before
 * the deviation is complete.
 *
 * Implements HasLabel + HasColor + HasIcon. Colors are code-level
 * presentation metadata (untranslated), per §0A.1 item 19.
 *
 * Stored values correspond to `users.role` (§2.12), which defaults to
 * `'warehouse_staff'` and is cast on the model (§3.18 `User`).
 *
 * Role semantics (A8 — policies are the ONLY home for permission logic):
 *   - Admin          — full system scope, including user management and
 *                      all warehouse operations.
 *   - Auditor        — read-only across the full system, including audit
 *                      ledgers and period filters. Never mutates
 *                      operational documents or stock, even when
 *                      assigned to warehouses (§8.3–§8.8).
 *   - WarehouseStaff — operational scope restricted to assigned
 *                      warehouses via the `user_warehouse` pivot
 *                      (§2.13). Badge scope follows role + warehouse
 *                      assignment (§1B.1a, A12).
 *   - BranchManager  — added in v13.6+, semantics codified in
 *                      v13.9.3 (§1B.1a five tiers, §8.3). WarehouseStaff
 *                      tier for badge scope (assigned union, no widening)
 *                      and reads; either-endpoint control for
 *                      confirm/negotiate/acceptRevision/rejectRevision on
 *                      transfers; create() keeps the ≥2-assignment gate;
 *                      no audit-filter authority; no Admin-only abilities.
 */
enum UserRole: string implements HasColor, HasIcon, HasLabel
{
    case Admin = 'admin';
    case Auditor = 'auditor';
    case WarehouseStaff = 'warehouse_staff';
    case BranchManager = 'branch_manager';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => __('enums.user_role.admin'),
            self::Auditor => __('enums.user_role.auditor'),
            self::WarehouseStaff => __('enums.user_role.warehouse_staff'),
            self::BranchManager => __('enums.user_role.branch_manager'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Auditor => 'info',
            self::WarehouseStaff => 'primary',
            self::BranchManager => 'warning',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Admin => Heroicon::OutlinedShieldCheck,
            self::Auditor => Heroicon::OutlinedEye,
            self::WarehouseStaff => Heroicon::OutlinedUsers,
            self::BranchManager => Heroicon::OutlinedBriefcase,
        };
    }
}
