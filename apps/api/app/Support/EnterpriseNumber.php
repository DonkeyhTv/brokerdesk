<?php

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * A Belgian enterprise number (BCE/KBO): ten digits, starting with 0 or 1,
 * whose last two digits are 97 minus the first eight modulo 97.
 */
final readonly class EnterpriseNumber implements JsonSerializable, Stringable
{
    private function __construct(public string $digits) {}

    public static function parse(string $value): self
    {
        $digits = preg_replace('/^BE|[\s.]/i', '', trim($value));

        if (! is_string($digits) || preg_match('/^[01]\d{9}$/', $digits) !== 1) {
            throw new InvalidArgumentException('An enterprise number has ten digits and starts with 0 or 1.');
        }

        if (self::checkDigits((int) substr($digits, 0, 8)) !== substr($digits, 8)) {
            throw new InvalidArgumentException('The enterprise number check digits are incorrect.');
        }

        return new self($digits);
    }

    /**
     * Build a valid number from its first eight digits.
     */
    public static function fromBase(int $base): self
    {
        if ($base < 0 || $base > 19_999_999) {
            throw new InvalidArgumentException('The base of an enterprise number is between 0 and 19999999.');
        }

        return new self(str_pad((string) $base, 8, '0', STR_PAD_LEFT).self::checkDigits($base));
    }

    public function formatted(): string
    {
        return sprintf('%s.%s.%s', substr($this->digits, 0, 4), substr($this->digits, 4, 3), substr($this->digits, 7));
    }

    public function __toString(): string
    {
        return $this->formatted();
    }

    public function jsonSerialize(): string
    {
        return $this->formatted();
    }

    private static function checkDigits(int $base): string
    {
        return str_pad((string) (97 - ($base % 97)), 2, '0', STR_PAD_LEFT);
    }
}
