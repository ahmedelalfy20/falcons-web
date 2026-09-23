<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalise a phone number into digits-only international form so the same
     * person cannot register twice by formatting their number differently.
     * Egyptian local numbers (01xxxxxxxxx) are converted to 201xxxxxxxxx.
     */
    public static function normalize(?string $raw): string
    {
        $raw = (string) $raw;
        // Convert Arabic-Indic and Persian digits to ASCII.
        $raw = strtr($raw, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '01')) {
            $digits = '2'.$digits;
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '1') && preg_match('/^1[0125]/', $digits)) {
            $digits = '20'.$digits;
        }

        return $digits;
    }

    public static function isValid(?string $raw): bool
    {
        $n = static::normalize($raw);

        return strlen($n) >= 8 && strlen($n) <= 15;
    }
}
