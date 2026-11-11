<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\Venue;

it('shows ticketing public pages', function () {
    $this->get('/')->assertSuccessful()->assertSee('Temukan event yang bikin harimu berkesan.');
    $this->get('/events')->assertSuccessful()->assertSee('Temukan pengalaman berikutnya.');
    $this->get('/faq')->assertSuccessful()->assertSee('FAQ');
    $this->get('/tentang-kami')->assertSuccessful()->assertSee('Tentang Kami');
});

it('shows upcoming events, categories, and search on the landing page', function () {
    $music = Category::factory()->create(['name' => 'Musik', 'slug' => 'musik', 'sort_order' => 1]);
    $jakarta = Venue::factory()->create(['city' => 'Jakarta']);

    $event = Event::factory()->featured()->create([
        'title' => 'Konser Beranda',
        'category_id' => $music->id,
        'venue_id' => $jakarta->id,
    ]);
    TicketType::factory()->forEvent($event)->create(['price' => 175000]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Konser Beranda')
        ->assertSee('Rp 175.000')
        ->assertSee('Musik')
        ->assertSee('Jakarta')
        ->assertSee('name="q"', false)
        ->assertSee('action="'.route('events.index').'"', false)
        ->assertSee('href="'.route('events.show', $event->slug).'"', false)
        ->assertSee('Cara beli tiket');
});

it('hides draft and past events from the landing page', function () {
    Event::factory()->create(['title' => 'Event Beranda Terbit']);
    Event::factory()->draft()->create(['title' => 'Event Beranda Draft']);
    Event::factory()->past()->create(['title' => 'Event Beranda Lewat']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Event Beranda Terbit')
        ->assertDontSee('Event Beranda Draft')
        ->assertDontSee('Event Beranda Lewat');
});

it('shows a friendly empty state when no events are available', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Event baru segera hadir')
        ->assertSee('Belum ada event mendatang');
});
