<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Event;
use App\Models\Faq;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class TicketingSeeder extends Seeder
{
    public function run(): void
    {
        $organizerUser = User::query()->firstOrCreate(
            ['email' => 'organizer@example.com'],
            ['name' => 'LinyTix', 'password' => 'password'],
        );
        $organizerUser->assignRole(Role::ORGANIZER);
        $organizerUser->forceFill(['is_active' => true])->save();

        $organizer = Organizer::query()->updateOrCreate(
            ['slug' => 'LinyTix'],
            [
                'owner_user_id' => $organizerUser->id,
                'name' => 'LinyTix',
                'description' => 'Penyelenggara konser, seminar, dan festival di Indonesia.',
                'contact_email' => 'halo@LinyTix.id',
                'contact_phone' => '6281234567890',
                'is_verified' => true,
                'verified_at' => now(),
            ],
        );

        $categories = collect([
            ['name' => 'Musik', 'slug' => 'musik', 'sort_order' => 1],
            ['name' => 'Seminar', 'slug' => 'seminar', 'sort_order' => 2],
            ['name' => 'Workshop', 'slug' => 'workshop', 'sort_order' => 3],
            ['name' => 'Festival', 'slug' => 'festival', 'sort_order' => 4],
        ])->mapWithKeys(function (array $data) {
            $category = Category::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data + ['is_active' => true],
            );

            return [$data['slug'] => $category];
        });

        $venues = [
            'jakarta' => Venue::query()->updateOrCreate(
                ['name' => 'Istora Senayan'],
                ['address' => 'Jl. Pintu Satu Senayan', 'city' => 'Jakarta', 'province' => 'DKI Jakarta', 'capacity' => 7000],
            ),
            'bandung' => Venue::query()->updateOrCreate(
                ['name' => 'Sabuga ITB'],
                ['address' => 'Jl. Tamansari No. 73', 'city' => 'Bandung', 'province' => 'Jawa Barat', 'capacity' => 3500],
            ),
            'makassar' => Venue::query()->updateOrCreate(
                ['name' => 'Celebes Convention Center'],
                ['address' => 'Jl. Metro Tanjung Bunga', 'city' => 'Makassar', 'province' => 'Sulawesi Selatan', 'capacity' => 2000],
            ),
        ];

        $events = [
            [
                'slug' => 'senja-fest-2027',
                'title' => 'Senja Fest 2027',
                'category' => 'musik',
                'venue' => 'jakarta',
                'status' => Event::STATUS_PUBLISHED,
                'featured' => true,
                'start' => now()->addDays(21)->setTime(19, 0),
                'short_desc' => 'Festival musik senja dengan lineup lintas genre.',
                'price' => 250000,
                'quota' => 500,
                'sold' => 120,
            ],
            [
                'slug' => 'seminar-kepemimpinan',
                'title' => 'Seminar Kepemimpinan',
                'category' => 'seminar',
                'venue' => 'makassar',
                'status' => Event::STATUS_PUBLISHED,
                'featured' => true,
                'start' => now()->addDays(35)->setTime(9, 0),
                'short_desc' => 'Membangun pemimpin adaptif untuk era digital.',
                'price' => 150000,
                'quota' => 200,
                'sold' => 80,
            ],
            [
                'slug' => 'workshop-produk-digital',
                'title' => 'Workshop Produk Digital',
                'category' => 'workshop',
                'venue' => 'bandung',
                'status' => Event::STATUS_PUBLISHED,
                'featured' => false,
                'start' => now()->addDays(14)->setTime(13, 0),
                'short_desc' => 'Dari ide ke rilis dalam satu hari.',
                'price' => 100000,
                'quota' => 60,
                'sold' => 60,
            ],
            [
                'slug' => 'festival-kuliner-nusantara',
                'title' => 'Festival Kuliner Nusantara',
                'category' => 'festival',
                'venue' => 'makassar',
                'status' => Event::STATUS_DRAFT,
                'featured' => false,
                'start' => now()->addDays(50)->setTime(10, 0),
                'short_desc' => 'Menjelajah cita rasa dari Sabang sampai Merauke.',
                'price' => 50000,
                'quota' => 300,
                'sold' => 0,
            ],
            [
                'slug' => 'konser-akustik-malam',
                'title' => 'Konser Akustik Malam',
                'category' => 'musik',
                'venue' => 'bandung',
                'status' => Event::STATUS_PUBLISHED,
                'featured' => false,
                'start' => now()->subMonth()->setTime(20, 0),
                'short_desc' => 'Malam intim bersama musisi akustik.',
                'price' => 200000,
                'quota' => 150,
                'sold' => 150,
            ],
        ];

        foreach ($events as $data) {
            $start = $data['start'];

            $event = Event::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'organizer_id' => $organizer->id,
                    'category_id' => $categories[$data['category']]->id,
                    'venue_id' => $venues[$data['venue']]->id,
                    'title' => $data['title'],
                    'short_desc' => $data['short_desc'],
                    'description' => $data['short_desc'].' '.fake()->paragraphs(2, true),
                    'terms' => 'Tiket yang sudah dibeli tidak dapat dikembalikan kecuali event dibatalkan oleh penyelenggara.',
                    'is_online' => false,
                    'timezone' => 'Asia/Jakarta',
                    'start_at' => $start,
                    'doors_open_at' => $start->copy()->subHour(),
                    'end_at' => $start->copy()->addHours(4),
                    'min_price' => $data['price'],
                    'status' => $data['status'],
                    'is_featured' => $data['featured'],
                    'published_at' => $data['status'] === Event::STATUS_PUBLISHED ? now() : null,
                ],
            );

            $event->ticketTypes()->updateOrCreate(
                ['name' => 'Regular'],
                [
                    'description' => 'Akses masuk area umum.',
                    'price' => $data['price'],
                    'quota' => $data['quota'],
                    'sold_count' => $data['sold'],
                    'min_per_order' => 1,
                    'max_per_order' => 4,
                    'is_active' => true,
                ],
            );

            $event->ticketTypes()->updateOrCreate(
                ['name' => 'VIP'],
                [
                    'description' => 'Akses area VIP dan merchandise eksklusif.',
                    'price' => $data['price'] * 2,
                    'quota' => max(10, (int) ($data['quota'] / 5)),
                    'sold_count' => 0,
                    'min_per_order' => 1,
                    'max_per_order' => 2,
                    'is_active' => true,
                ],
            );

            if ($data['featured']) {
                Banner::query()->updateOrCreate(
                    ['event_id' => $event->id],
                    [
                        'image_url' => $event->displayBannerUrl(),
                        'link_url' => null,
                        'sort_order' => 1,
                        'start_at' => now()->subDay(),
                        'end_at' => now()->addMonths(2),
                        'is_active' => true,
                    ],
                );
            }
        }

        foreach ([
            ['Bagaimana cara membeli tiket?', 'Pilih event, tentukan jenis dan jumlah tiket, lalu selesaikan pembayaran sebelum batas waktu.'],
            ['Apakah tiket bisa dikembalikan?', 'Tiket dapat dikembalikan jika event dibatalkan oleh penyelenggara. Permintaan refund lain diproses sesuai kebijakan.'],
            ['Bagaimana cara masuk ke event?', 'Tunjukkan QR e-tiket di pintu masuk. Petugas akan memindai kode tiket Anda.'],
        ] as $index => [$question, $answer]) {
            Faq::query()->updateOrCreate(
                ['question' => $question],
                ['answer' => $answer, 'sort_order' => $index + 1, 'is_active' => true],
            );
        }
    }
}
