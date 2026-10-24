<?php

declare(strict_types=1);

namespace App\Casts;

use App\Enums\UserRole;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UserRoleCast implements CastsAttributes
{
    /**
     * Cast the given value from the database.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?UserRole
    {
        if ($value === null) {
            return null;
        }

        $stringValue = (string) $value;
        $role = UserRole::tryFrom($stringValue);

        if ($role === null) {
            Log::warning('invalid_user_role', [
                'user_id' => $model->getKey(),
                'raw_role' => $stringValue,
            ]);
            return null;
        }

        return $role;
    }

    /**
     * Prepare the given value for storage.
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

        throw new InvalidArgumentException("Invalid UserRole value: " . (is_scalar($value) ? (string) $value : gettype($value)));
    }
}
