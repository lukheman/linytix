<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(['email' => 'admin@gmail.com'], [
            'name' => 'Admin',
            'password' => 'password123',
        ]);
        $this->call(RoleSeeder::class);
        $this->call(DomainSeeder::class);
        $this->call(TicketingSeeder::class);

        foreach ([
            'site_name' => 'LinyTix',
            'footer_tagline' => 'Temukan event dari berbagai kota di Indonesia dan terima e-tiket QR langsung di email.',
            'hero_eyebrow' => 'Event di seluruh Indonesia',
            'hero_title' => 'Temukan event pilihan di seluruh Indonesia.',
            'hero_subtitle' => 'Cari konser, seminar, workshop, festival, dan event lainnya berdasarkan kota, kategori, atau tanggal. Pesan tiket online dan terima e-tiket QR langsung di email.',
            'hero_note' => 'E-tiket QR · Akses pesanan tanpa akun · Informasi event transparan',
            'email' => 'halo@LinyTix.id',
            'whatsapp_number' => '6281234567890',
            'whatsapp_template' => 'Halo, saya ingin bertanya tentang pembelian tiket event.',
        ] as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
