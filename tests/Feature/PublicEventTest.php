<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketType;
use App\Models\Venue;

it('lists only published upcoming events', function () {
    $published = Event::factory()->create(['title' => 'Konser Terbit']);
    TicketType::factory()->forEvent($published)->create(['price' => 150000]);

    $draft = Event::factory()->draft()->create(['title' => 'Event Draft Rahasia']);
    $past = Event::factory()->past()->create(['title' => 'Event Sudah Lewat']);

    $this->get('/events')
        ->assertOk()
        ->assertSee('Konser Terbit')
        ->assertSee('Rp 150.000')
        ->assertDontSee('Event Draft Rahasia')
        ->assertDontSee('Event Sudah Lewat');

    $this->get('/events/'.$draft->slug)->assertNotFound();
    $this->get('/events/'.$past->slug)->assertNotFound();
});

it('filters events by category city keyword and price', function () {
    $jakarta = Venue::factory()->create(['city' => 'Jakarta']);
    $bandung = Venue::factory()->create(['city' => 'Bandung']);

    $music = Category::factory()->create(['name' => 'Musik', 'slug' => 'musik']);
    $seminar = Category::factory()->create(['name' => 'Seminar', 'slug' => 'seminar']);

    $concert = Event::factory()->create([
        'title' => 'Jakarta Music Night',
        'category_id' => $music->id,
        'venue_id' => $jakarta->id,
    ]);
    TicketType::factory()->forEvent($concert)->create(['price' => 300000]);

    $talk = Event::factory()->create([
        'title' => 'Bandung Leadership Talk',
        'category_id' => $seminar->id,
        'venue_id' => $bandung->id,
    ]);
    TicketType::factory()->forEvent($talk)->create(['price' => 100000]);

    $this->get('/events?category=musik')->assertOk()->assertSee('Jakarta Music Night')->assertDontSee('Bandung Leadership Talk');
    $this->get('/events?city=Bandung')->assertOk()->assertSee('Bandung Leadership Talk')->assertDontSee('Jakarta Music Night');
    $this->get('/events?q=Leadership')->assertOk()->assertSee('Bandung Leadership Talk')->assertDontSee('Jakarta Music Night');
    $this->get('/events?price_max=150000')->assertOk()->assertSee('Bandung Leadership Talk')->assertDontSee('Jakarta Music Night');
});

it('ignores invalid filter values without failing', function () {
    Event::factory()->create(['title' => 'Event Aman']);

    $this->get('/events?date_from=bukan-tanggal&price_min=abc&sort=ngawur')
        ->assertOk()
        ->assertSee('Event Aman');
});

it('shows event detail with ticket availability and structured data', function () {
    $event = Event::factory()->create([
        'title' => 'Festival Uji',
        'short_desc' => 'Deskripsi singkat festival uji.',
    ]);
    TicketType::factory()->forEvent($event)->create([
        'name' => 'Regular',
        'price' => 125000,
        'quota' => 100,
        'sold_count' => 40,
    ]);

    $this->get('/events/'.$event->slug)
        ->assertOk()
        ->assertSee('Festival Uji')
        ->assertSee('Regular')
        ->assertSee('Rp 125.000')
        ->assertSee('Sisa 60 tiket')
        ->assertSee('application/ld+json', false)
        ->assertSee('"@type":"Event"', false);
});

it('does not leak the online event url on the public page', function () {
    $event = Event::factory()->online()->create(['title' => 'Webinar Rahasia']);

    $this->get('/events/'.$event->slug)
        ->assertOk()
        ->assertSee('Webinar Rahasia')
        ->assertDontSee($event->online_url);
});

it('marks sold out events and disables purchase', function () {
    $event = Event::factory()->create(['title' => 'Event Penuh']);
    TicketType::factory()->forEvent($event)->create(['quota' => 10, 'sold_count' => 10]);

    $this->get('/events/'.$event->slug)
        ->assertOk()
        ->assertSee('Tiket habis');
});

it('renders the public information pages', function () {
    $this->get('/faq')->assertOk()->assertSee('FAQ');
    $this->get('/tentang-kami')->assertOk()->assertSee('Tentang Kami');
    $this->get('/privacy-policy')->assertOk()->assertSee('Kebijakan Privasi');
    $this->get('/terms')->assertOk()->assertSee('Syarat & Ketentuan');
    $this->get('/refund-policy')->assertOk()->assertSee('Kebijakan Refund');
});

it('exposes published events in the sitemap', function () {
    $event = Event::factory()->create(['title' => 'Event Sitemap']);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('events.show', $event->slug), false);
});

it('uses the organizer from the event relation on the detail page', function () {
    $organizer = Organizer::factory()->create(['name' => 'Promotor Uji']);
    $event = Event::factory()->forOrganizer($organizer)->create();

    $this->get('/events/'.$event->slug)
        ->assertOk()
        ->assertSee('Promotor Uji')
        ->assertSee('Organizer terverifikasi');
});
