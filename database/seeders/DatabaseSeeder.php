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
            'footer_tagline' => 'Beli tiket event favoritmu dengan pembayaran aman dan e-tiket QR.',
            'hero_eyebrow' => 'Platform tiket event',
            'hero_title' => 'Temukan event yang bikin harimu berkesan.',
            'hero_subtitle' => 'Konser, seminar, workshop, dan festival pilihan. Pesan tiket online, bayar aman, dan terima e-tiket QR langsung di email.',
            'hero_note' => 'Tiket asli, QR unik, dan akses pesanan kapan saja.',
            'email' => 'halo@LinyTix.id',
            'whatsapp_number' => '6281234567890',
            'whatsapp_template' => 'Halo, saya ingin bertanya tentang pembelian tiket event.',
        ] as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
