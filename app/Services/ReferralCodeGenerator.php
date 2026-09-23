<?php

namespace App\Services;

use App\Models\Leader;
use Illuminate\Support\Str;
use RuntimeException;

class ReferralCodeGenerator
{
    // No 0/O/1/I/L to keep codes readable when typed by hand.
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public function code(): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = 'LDR-'.$this->random(8);
            if (! Leader::where('unique_code', $code)->exists()) {
                return $code;
            }
        }
        throw new RuntimeException('Unable to generate a unique referral code.');
    }

    public function qrToken(): string
    {
        return Str::random(32);
    }

    private function random(int $length): string
    {
        $out = '';
        $max = strlen(self::ALPHABET) - 1;
        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)]; // CSPRNG
        }

        return $out;
    }

    public static function normalizeInput(?string $code): string
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code) ?? '');
        if (str_starts_with($code, 'LDR')) {
            $code = substr($code, 3);
        }

        return $code === '' ? '' : 'LDR-'.$code;
    }
}
