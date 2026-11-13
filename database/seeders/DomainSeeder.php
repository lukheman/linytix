<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            $user = User::factory()->create();
        }

        $organizer = Organizer::query()->firstOrCreate(['slug' => 'LinyTix'], [
            'owner_user_id' => $user->id,
            'name' => 'LinyTix',
            'description' => 'Penyelenggara event profesional.',
            'is_verified' => true,
        ]);
        $category = Category::query()->firstOrCreate(['slug' => 'seminar'], ['name' => 'Seminar', 'is_active' => true]);
        $venue = Venue::query()->firstOrCreate(['name' => 'Gedung Merdeka'], ['slug' => 'gedung-merdeka', 'address' => 'Jl. Merdeka No. 10', 'city' => 'Makassar', 'province' => 'Sulawesi Selatan', 'capacity' => 500, 'is_active' => true]);
        $event = Event::query()->firstOrCreate(['slug' => 'seminar-kepemimpinan'], [
            'organizer_id' => $organizer->id, 'category_id' => $category->id, 'venue_id' => $venue->id,
            'title' => 'Seminar Kepemimpinan', 'description' => 'Seminar pengembangan kepemimpinan.',
            'start_at' => now()->addMonth(), 'end_at' => now()->addMonth()->addHours(4), 'status' => 'published',
        ]);
        TicketType::query()->firstOrCreate(['event_id' => $event->id, 'name' => 'Regular'], ['price' => 100000, 'quota' => 100, 'is_active' => true]);
    }
}
