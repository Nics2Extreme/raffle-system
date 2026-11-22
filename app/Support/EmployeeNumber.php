<?php

namespace App\Support;

use InvalidArgumentException;

class EmployeeNumber
{
    public static function normalize(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '' || strlen($digits) > 4) {
            throw new InvalidArgumentException('Employee number must contain 1 to 4 digits.');
        }

        return str_pad($digits, 4, '0', STR_PAD_LEFT);
    }

    public static function tryNormalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return self::normalize($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
