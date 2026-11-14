<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $copy = [
            'footer_tagline' => 'Temukan event dari berbagai kota di Indonesia dan terima e-tiket QR langsung di email.',
            'hero_eyebrow' => 'Event di seluruh Indonesia',
            'hero_title' => 'Temukan event pilihan di seluruh Indonesia.',
            'hero_subtitle' => 'Cari konser, seminar, workshop, festival, dan event lainnya berdasarkan kota, kategori, atau tanggal. Pesan tiket online dan terima e-tiket QR langsung di email.',
            'hero_note' => 'E-tiket QR · Akses pesanan tanpa akun · Informasi event transparan',
        ];

        foreach ($copy as $key => $value) {
            SiteSetting::query()->where('key', $key)->update(['value' => $value]);
        }
    }

    public function down(): void
    {
        $copy = [
            'footer_tagline' => 'Beli tiket event favoritmu dengan pembayaran aman dan e-tiket QR.',
            'hero_eyebrow' => 'Platform tiket event',
            'hero_title' => 'Temukan event yang bikin harimu berkesan.',
            'hero_subtitle' => 'Konser, seminar, workshop, dan festival pilihan. Pesan tiket online, bayar aman, dan terima e-tiket QR langsung di email.',
            'hero_note' => 'Tiket asli, QR unik, dan akses pesanan kapan saja.',
        ];

        foreach ($copy as $key => $value) {
            SiteSetting::query()->where('key', $key)->update(['value' => $value]);
        }
    }
};
