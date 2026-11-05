<?php

namespace App\Support;

final class Csv
{
    /**
     * Spreadsheets execute cells starting with these characters as formulas.
     */
    public static function safeCell(string $value): string
    {
        return in_array(substr($value, 0, 1), ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
