<?php

namespace App\Support;

use App\Models\SiteSetting;

class SiteContent
{
    /** @return array<string, string> */
    public static function settings(): array
    {
        return SiteSetting::query()->pluck('value', 'key')->all();
    }

    public static function whatsappUrl(array $settings, string $message = ''): string
    {
        $number = preg_replace('/\D+/', '', $settings['whatsapp_number'] ?? '6281234567890');
        $text = $message ?: ($settings['whatsapp_template'] ?? 'Halo, saya ingin bertanya tentang pembelian tiket event.');

        return 'https://wa.me/'.$number.'?text='.urlencode($text);
    }
}
