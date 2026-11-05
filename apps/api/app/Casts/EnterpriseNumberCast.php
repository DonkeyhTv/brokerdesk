<?php

namespace App\Casts;

use App\Support\EnterpriseNumber;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<EnterpriseNumber, EnterpriseNumber|string>
 */
class EnterpriseNumberCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?EnterpriseNumber
    {
        return $value === null ? null : EnterpriseNumber::parse($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof EnterpriseNumber ? $value->digits : EnterpriseNumber::parse($value)->digits;
    }
}
