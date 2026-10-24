<?php

declare(strict_types=1);

namespace App\Casts;

use App\Enums\UserRole;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use ValueError;

class UserRoleCast implements CastsAttributes
{
    /**
     * Cast the given value from the database.
     *
     * Strict contract (#205): Throws ValueError on read if raw database string
     * does not match a valid UserRole enum backing value.
     * Log warning is rate-limited / deduped per user_id + raw_role (60s TTL cache).
     *
     * @throws ValueError
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?UserRole
    {
        if ($value === null) {
            return null;
        }

        $stringValue = (string) $value;
        $role = UserRole::tryFrom($stringValue);

        if ($role === null) {
            $userId = $model->getKey() ?? 'unpersisted';
            $cacheKey = "invalid_user_role_logged_{$userId}_{$stringValue}";

            if (! Cache::has($cacheKey)) {
                Cache::put($cacheKey, true, 60);
                Log::warning('invalid_user_role', [
                    'user_id' => $userId,
                    'raw_role' => $stringValue,
                ]);
            }

            throw new ValueError("{$stringValue} is not a valid backing value for enum App\\Enums\\UserRole");
        }

        return $role;
    }

    /**
     * Prepare the given value for storage.
     *
     * @throws InvalidArgumentException
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof UserRole) {
            return $value->value;
        }

        if (is_string($value)) {
            $role = UserRole::tryFrom($value);
            if ($role !== null) {
                return $role->value;
            }
        }

        throw new InvalidArgumentException('Invalid UserRole value: ' . (is_scalar($value) ? (string) $value : gettype($value)));
    }
}
