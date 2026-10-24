<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\UserRoleCast;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use ValueError;

final class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Mass-assignable attributes (§2.12).
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    /**
     * Attributes hidden from serialization.
     *
     * `password` and `remember_token` are Laravel defaults; the two
     * `app_authentication_*` attributes support the Filament MFA
     * extension implemented by this model.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];

    /**
     * Attribute casts (§3.18 — blueprint-mandated casts).
     *
     * Blueprint §3.18 declares `role`, `is_active`, and `password`.
     * The additional casts support the Filament MFA extension this
     * model implements and the Laravel default `email_verified_at`
     * column — see the class docblock for the schema additions the
     * MFA casts require.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // §3.18 — blueprint-mandated.
        'role' => UserRoleCast::class,
        'is_active' => 'boolean',
        'password' => 'hashed',

        // Extension — Filament MFA (see class docblock for the schema
        // additions these two casts require).
        'app_authentication_secret' => 'encrypted',
        'app_authentication_recovery_codes' => 'encrypted:array',

        // Extension — Laravel default column; harmless when email
        // verification is unused (MustVerifyEmail is not implemented).
        'email_verified_at' => 'datetime',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        try {
            return $this->role instanceof UserRole;
        } catch (ValueError $e) {
            return false;
        }
    }

    public function isAdmin(): bool
    {
        try {
            return $this->role === UserRole::Admin;
        } catch (ValueError $e) {
            return false;
        }
    }

    public function isAuditor(): bool
    {
        try {
            return $this->role === UserRole::Auditor;
        } catch (ValueError $e) {
            return false;
        }
    }

    public function isWarehouseStaff(): bool
    {
        try {
            return $this->role === UserRole::WarehouseStaff;
        } catch (ValueError $e) {
            return false;
        }
    }

    public function isBranchManager(): bool
    {
        try {
            return $this->role === UserRole::BranchManager;
        } catch (ValueError $e) {
            return false;
        }
    }

    /**
     * @return BelongsToMany<Warehouse, $this>
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouse');
    }

    public function hasAccessToWarehouse(int $warehouseId): bool
    {
        try {
            if ($this->role === null) {
                return false;
            }

            return $this->isAdmin()
                || $this->isAuditor()
                || (($this->isWarehouseStaff() || $this->isBranchManager())
                    && $this->warehouses()->where('warehouses.id', $warehouseId)->exists());
        } catch (ValueError $e) {
            return false;
        }
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    /** @phpstan-ignore-next-line */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }
}
