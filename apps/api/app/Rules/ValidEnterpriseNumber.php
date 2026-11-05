<?php

namespace App\Rules;

use App\Support\EnterpriseNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class ValidEnterpriseNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            EnterpriseNumber::parse((string) $value);
        } catch (InvalidArgumentException) {
            $fail('The :attribute is not a valid Belgian enterprise number.');
        }
    }
}
