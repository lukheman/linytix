<?php

namespace App\Support;

/**
 * Masking data pribadi agar halaman admin tidak menampilkan PII berlebih.
 */
class Pii
{
    /**
     * Tampilkan hanya empat karakter terakhir nomor identitas.
     */
    public static function maskIdentity(?string $value): ?string
    {
        return self::maskTail($value);
    }

    /**
     * Tampilkan hanya empat digit terakhir nomor telepon.
     */
    public static function maskPhone(?string $value): ?string
    {
        return self::maskTail($value);
    }

    private static function maskTail(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', $value) ?? $value;

        if (mb_strlen($normalized) <= 4) {
            return '****';
        }

        return '****'.mb_substr($normalized, -4);
    }
}
