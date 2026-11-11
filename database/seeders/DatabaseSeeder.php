<?php

namespace Database\Seeders;

use App\Models\Collaboration;
use App\Models\RentalItem;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Models\Testimonial;
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

        $services = [
            ['category' => 'pelatihan', 'name' => 'Pelatihan', 'slug' => 'pelatihan', 'description' => 'Program pelatihan interaktif untuk meningkatkan kapasitas tim dan organisasi.', 'features' => ['Modul terstruktur', 'Fasilitator berpengalaman', 'Dokumentasi kegiatan'], 'sort_order' => 1],
            ['category' => 'workshop', 'name' => 'Workshop', 'slug' => 'workshop', 'description' => 'Workshop praktis yang dirancang sesuai tujuan dan kebutuhan peserta.', 'features' => ['Konsep custom', 'Aktivitas kolaboratif', 'Evaluasi pasca-acara'], 'sort_order' => 2],
            ['category' => 'seminar', 'name' => 'Seminar', 'slug' => 'seminar', 'description' => 'Seminar profesional dengan alur acara yang terencana dan pengalaman peserta yang nyaman.', 'features' => ['Rundown acara', 'Manajemen pembicara', 'Registrasi peserta'], 'sort_order' => 3],
            ['category' => 'penyewaan', 'name' => 'Penyewaan Alat', 'slug' => 'penyewaan', 'description' => 'Sewa tenda, sound system, lighting, kursi, panggung, dan perlengkapan acara lainnya.', 'features' => ['Tenda & venue', 'Sound & lighting', 'Kursi, meja & panggung'], 'sort_order' => 4],
        ];
        foreach ($services as $service) {
            Service::updateOrCreate(['slug' => $service['slug']], $service);
        }

        foreach ([
            'site_name' => 'Nusa Event',
            'footer_tagline' => 'Sewa perlengkapan event dengan antar, pasang, dan penarikan.',
            'hero_eyebrow' => 'Penyewaan alat event',
            'hero_title' => 'Sewa perlengkapan acara, lengkap dan siap pakai.',
            'hero_subtitle' => 'Tenda, sound system, lighting, kursi, panggung, hingga genset. Cek ketersediaan dan pesan langsung via WhatsApp.',
            'hero_note' => 'Antar, pasang, dan penarikan oleh tim kami.',
            'about' => 'Nusa Event menyediakan penyewaan perlengkapan acara untuk instansi, sekolah, perusahaan, dan komunitas. Kami membantu dari pengecekan stok hingga pemasangan di lokasi.',
            'vision' => 'Menjadi partner penyewaan perlengkapan acara yang paling andal.',
            'mission' => 'Menyediakan alat yang terawat, pengiriman tepat waktu, dan pendampingan teknis di lokasi acara.',
            'address' => 'Jl. Merdeka No. 10, Makassar',
            'email' => 'halo@nusaevent.id',
            'whatsapp_number' => '6281234567890',
            'whatsapp_template' => 'Halo, saya ingin menanyakan ketersediaan alat untuk acara saya.',
            'operating_hours' => 'Senin–Sabtu, 08.00–17.00',
        ] as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $rentalService = Service::query()->where('slug', 'penyewaan')->firstOrFail();

        foreach ([
            ['category' => 'tenda', 'name' => 'Tenda Roder 5x10', 'description' => 'Tenda rangka untuk acara outdoor dengan kapasitas peserta besar.', 'sort_order' => 1],
            ['category' => 'tenda', 'name' => 'Tenda Sarnafil', 'description' => 'Tenda praktis untuk acara komunitas, bazar, dan gathering.', 'sort_order' => 2],
            ['category' => 'sound', 'name' => 'Sound System 5000 Watt', 'description' => 'Paket sound system untuk seminar, konser mini, dan acara sekolah.', 'sort_order' => 3],
            ['category' => 'sound', 'name' => 'Wireless Microphone', 'description' => 'Mikrofon nirkabel untuk pembicara dan moderator.', 'sort_order' => 4],
            ['category' => 'lighting', 'name' => 'Lighting Par LED', 'description' => 'Lampu panggung untuk memperkuat suasana acara.', 'sort_order' => 5],
            ['category' => 'lighting', 'name' => 'Moving Head', 'description' => 'Lampu bergerak untuk panggung dan pertunjukan.', 'sort_order' => 6],
            ['category' => 'furniture', 'name' => 'Kursi Futura', 'description' => 'Kursi lipat untuk peserta seminar dan resepsi.', 'sort_order' => 7],
            ['category' => 'furniture', 'name' => 'Meja Bundar', 'description' => 'Meja bundar untuk jamuan, registrasi, dan area peserta.', 'sort_order' => 8],
            ['category' => 'multimedia', 'name' => 'Proyektor & Layar', 'description' => 'Paket proyektor dan layar untuk presentasi acara.', 'sort_order' => 9],
            ['category' => 'multimedia', 'name' => 'LED Screen', 'description' => 'Layar LED untuk backdrop dan visual acara.', 'sort_order' => 10],
            ['category' => 'power', 'name' => 'Genset 10 kVA', 'description' => 'Sumber daya cadangan untuk acara di lokasi tanpa listrik memadai.', 'sort_order' => 11],
            ['category' => 'lainnya', 'name' => 'Panggung Portable', 'description' => 'Panggung modular untuk talkshow, band, dan upacara.', 'sort_order' => 12],
        ] as $item) {
            $item['photo'] = 'dummy-rental/'.match ($item['category']) {
                'tenda' => 'tenda.svg',
                'sound' => 'sound.svg',
                'lighting' => 'lighting.svg',
                'furniture' => 'furniture.svg',
                'multimedia' => 'multimedia.svg',
                'power' => 'power.svg',
                default => 'lainnya.svg',
            };

            RentalItem::updateOrCreate(
                ['service_id' => $rentalService->id, 'name' => $item['name']],
                $item + ['is_active' => true],
            );
        }

        SocialLink::updateOrCreate(['platform' => 'Instagram'], ['url' => 'https://instagram.com', 'icon' => 'fa-brands fa-instagram', 'sort_order' => 1]);

        foreach ([
            ['name' => 'Rina Pratama', 'organization' => 'SMK Harapan', 'comment' => 'Tenda dan sound system datang tepat waktu. Pemasangan rapi dan timnya responsif.', 'rating' => 5],
            ['name' => 'Andi Saputra', 'organization' => 'PT Karya Nusantara', 'comment' => 'Sewa kursi, meja, dan proyektor untuk acara kantor. Semua sesuai kebutuhan.', 'rating' => 5],
            ['name' => 'Maya Lestari', 'organization' => 'Komunitas Tumbuh', 'comment' => 'Koordinasi pengiriman jelas dan alat yang dipakai berfungsi dengan baik.', 'rating' => 5],
        ] as $testimonial) {
            Testimonial::firstOrCreate(['name' => $testimonial['name']], $testimonial);
        }

        foreach ([
            ['partner_name' => 'SMK Harapan', 'event_name' => 'Seminar Kepemimpinan', 'slug' => 'seminar-kepemimpinan-smk-harapan', 'event_date' => now()->subMonths(2), 'service_category' => 'seminar', 'description' => 'Seminar pengembangan kepemimpinan untuk siswa dengan alur acara yang terencana.'],
            ['partner_name' => 'PT Karya Nusantara', 'event_name' => 'Workshop Kolaborasi Tim', 'slug' => 'workshop-kolaborasi-tim', 'event_date' => now()->subMonths(5), 'service_category' => 'workshop', 'description' => 'Workshop peningkatan kolaborasi lintas divisi untuk tim internal.'],
            ['partner_name' => 'Komunitas Tumbuh', 'event_name' => 'Pelatihan Fasilitator Muda', 'slug' => 'pelatihan-fasilitator-muda', 'event_date' => now()->subMonths(9), 'service_category' => 'pelatihan', 'description' => 'Pelatihan fasilitator untuk komunitas dengan modul dan pendampingan.'],
        ] as $collaboration) {
            Collaboration::updateOrCreate(['slug' => $collaboration['slug']], $collaboration);
        }
    }
}
