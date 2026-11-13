<?php

use App\Models\AuditLog;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Event;
use App\Models\Faq;
use App\Models\User;
use App\Models\Venue;
use App\Support\PublicCache;
use Illuminate\Support\Facades\Cache;

function masterEvent(Category $category, Venue $venue, array $overrides = []): Event
{
    return Event::factory()->create(array_merge([
        'category_id' => $category->getKey(),
        'venue_id' => $venue->getKey(),
        'status' => Event::STATUS_PUBLISHED,
        'start_at' => now()->addDays(7),
        'published_at' => now(),
    ], $overrides));
}

it('lets an admin manage categories', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.categories.store'), [
        'name' => 'Kategori Baru',
        'slug' => '',
        'icon_url' => 'https://cdn.example.test/icon.png',
        'sort_order' => 3,
        'is_active' => '1',
    ])->assertRedirect(route('admin.categories'))
        ->assertSessionHas('success');

    $category = Category::query()->where('name', 'Kategori Baru')->firstOrFail();

    expect($category->slug)->toBe('kategori-baru')
        ->and($category->sort_order)->toBe(3)
        ->and($category->is_active)->toBeTrue();

    $this->actingAs($admin)->put(route('admin.categories.update', $category), [
        'name' => 'Kategori Diperbarui',
        'slug' => 'kategori-updated',
        'sort_order' => 5,
        'is_active' => '0',
    ])->assertRedirect(route('admin.categories'));

    $category->refresh();

    expect($category->name)->toBe('Kategori Diperbarui')
        ->and($category->slug)->toBe('kategori-updated')
        ->and($category->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories'));

    expect(Category::find($category->getKey()))->toBeNull();

    $this->assertDatabaseHas('audit_logs', ['action' => 'category.created', 'entity' => 'category']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'category.updated', 'entity' => 'category']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'category.deleted', 'entity' => 'category']);
});

it('lets an admin manage venues with unique slugs', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.venues.store'), [
        'name' => 'Venue Baru',
        'slug' => '',
        'address' => 'Jl. Contoh No. 1',
        'city' => 'Makassar',
        'province' => 'Sulawesi Selatan',
        'capacity' => 1500,
        'latitude' => -5.1477,
        'longitude' => 119.4327,
        'maps_url' => 'https://maps.example.test/venue',
        'is_active' => '1',
    ])->assertRedirect(route('admin.venues'))
        ->assertSessionHas('success');

    $venue = Venue::query()->where('name', 'Venue Baru')->firstOrFail();

    expect($venue->slug)->toBe('venue-baru')
        ->and($venue->capacity)->toBe(1500)
        ->and($venue->is_active)->toBeTrue();

    $this->actingAs($admin)->put(route('admin.venues.update', $venue), [
        'name' => 'Venue Diperbarui',
        'slug' => '',
        'address' => 'Jl. Contoh No. 2',
        'city' => 'Bandung',
        'province' => 'Jawa Barat',
        'capacity' => 2000,
        'is_active' => '0',
    ])->assertRedirect(route('admin.venues'));

    $venue->refresh();

    expect($venue->slug)->toBe('venue-diperbarui')
        ->and($venue->city)->toBe('Bandung')
        ->and($venue->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->delete(route('admin.venues.destroy', $venue))
        ->assertRedirect(route('admin.venues'));

    expect(Venue::find($venue->getKey()))->toBeNull();

    $this->assertDatabaseHas('audit_logs', ['action' => 'venue.created', 'entity' => 'venue']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'venue.updated', 'entity' => 'venue']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'venue.deleted', 'entity' => 'venue']);
});

it('lets an admin manage faqs', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.faqs.store'), [
        'question' => 'Apakah tiket bisa dipindah tangan?',
        'answer' => 'Tiket dapat dipindahtangankan sesuai ketentuan event.',
        'sort_order' => 2,
        'is_active' => '1',
    ])->assertRedirect(route('admin.faqs'));

    $faq = Faq::query()->where('question', 'like', 'Apakah tiket bisa%')->firstOrFail();

    $this->actingAs($admin)->put(route('admin.faqs.update', $faq), [
        'question' => 'Apakah tiket bisa dipindahtangankan?',
        'answer' => 'Hubungi organizer untuk ketentuan lebih lanjut.',
        'sort_order' => 4,
        'is_active' => '0',
    ])->assertRedirect(route('admin.faqs'));

    $faq->refresh();

    expect($faq->sort_order)->toBe(4)
        ->and($faq->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->delete(route('admin.faqs.destroy', $faq))
        ->assertRedirect(route('admin.faqs'));

    expect(Faq::find($faq->getKey()))->toBeNull();

    $this->assertDatabaseHas('audit_logs', ['action' => 'faq.created', 'entity' => 'faq']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'faq.updated', 'entity' => 'faq']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'faq.deleted', 'entity' => 'faq']);
});

it('lets an admin manage banners and validates the date range', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)->post(route('admin.banners.store'), [
        'event_id' => $event->getKey(),
        'image_url' => 'https://cdn.example.test/banner-baru.png',
        'link_url' => 'https://example.test/promo',
        'sort_order' => 1,
        'start_at' => '2026-12-01T10:00',
        'end_at' => '2026-11-01T10:00',
        'is_active' => '1',
    ])->assertSessionHasErrors('end_at');

    expect(Banner::query()->count())->toBe(0);

    $this->actingAs($admin)->post(route('admin.banners.store'), [
        'event_id' => $event->getKey(),
        'image_url' => 'https://cdn.example.test/banner-baru.png',
        'link_url' => 'https://example.test/promo',
        'sort_order' => 1,
        'start_at' => '2026-12-01T10:00',
        'end_at' => '2026-12-31T10:00',
        'is_active' => '1',
    ])->assertRedirect(route('admin.banners'));

    $banner = Banner::query()->firstOrFail();

    $this->actingAs($admin)->put(route('admin.banners.update', $banner), [
        'event_id' => $event->getKey(),
        'image_url' => 'https://cdn.example.test/banner-updated.png',
        'link_url' => 'https://example.test/promo-baru',
        'sort_order' => 2,
        'start_at' => '2026-12-01T10:00',
        'end_at' => '2026-12-31T10:00',
        'is_active' => '0',
    ])->assertRedirect(route('admin.banners'));

    $banner->refresh();

    expect($banner->sort_order)->toBe(2)
        ->and($banner->is_active)->toBeFalse()
        ->and($banner->image_url)->toBe('https://cdn.example.test/banner-updated.png');

    $this->actingAs($admin)
        ->delete(route('admin.banners.destroy', $banner))
        ->assertRedirect(route('admin.banners'));

    expect(Banner::find($banner->getKey()))->toBeNull();

    $this->assertDatabaseHas('audit_logs', ['action' => 'banner.created', 'entity' => 'banner']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'banner.updated', 'entity' => 'banner']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'banner.deleted', 'entity' => 'banner']);
});

it('validates duplicate category names and venue slugs', function () {
    $admin = User::factory()->admin()->create();

    Category::factory()->create(['name' => 'Duplikat Kategori']);
    Venue::factory()->create(['name' => 'Venue Sama', 'slug' => 'venue-sama']);

    $this->actingAs($admin)->post(route('admin.categories.store'), [
        'name' => 'Duplikat Kategori',
        'is_active' => '1',
    ])->assertSessionHasErrors('name');

    $this->actingAs($admin)->post(route('admin.venues.store'), [
        'name' => 'Venue Sama',
        'address' => 'Jl. Baru',
        'city' => 'Jakarta',
        'province' => 'DKI Jakarta',
        'is_active' => '1',
    ])->assertSessionHasErrors('slug');
});

it('prevents deleting categories and venues that are still used by events', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();
    $venue = Venue::factory()->create();

    masterEvent($category, $venue);

    $this->actingAs($admin)
        ->from(route('admin.categories'))
        ->delete(route('admin.categories.destroy', $category))
        ->assertSessionHasErrors('category');

    $this->actingAs($admin)
        ->from(route('admin.venues'))
        ->delete(route('admin.venues.destroy', $venue))
        ->assertSessionHasErrors('venue');

    expect(Category::find($category->getKey()))->not->toBeNull()
        ->and(Venue::find($venue->getKey()))->not->toBeNull();
});

it('only shows active master data on public pages', function () {
    $admin = User::factory()->admin()->create();

    $category = Category::factory()->create([
        'name' => 'Kategori Aktif Publik',
        'slug' => 'kategori-aktif-publik',
        'is_active' => true,
    ]);

    $venue = Venue::factory()->create([
        'name' => 'Venue Publik',
        'slug' => 'venue-publik',
        'city' => 'Kota Unik Publik',
        'is_active' => true,
    ]);

    masterEvent($category, $venue);

    $bannerEvent = Event::factory()->create([
        'status' => Event::STATUS_PUBLISHED,
        'start_at' => now()->addDays(3),
        'published_at' => now(),
    ]);

    Faq::factory()->create([
        'question' => 'FAQ aktif tampil?',
        'answer' => 'Ya.',
        'is_active' => true,
    ]);

    Faq::factory()->create([
        'question' => 'FAQ nonaktif tersembunyi?',
        'answer' => 'Tidak tampil.',
        'is_active' => false,
    ]);

    Banner::factory()->create([
        'event_id' => $bannerEvent->getKey(),
        'image_url' => 'https://cdn.example.test/banner-aktif.png',
        'start_at' => now()->subDay(),
        'end_at' => now()->addMonth(),
        'is_active' => true,
    ]);

    Banner::factory()->create([
        'event_id' => $bannerEvent->getKey(),
        'image_url' => 'https://cdn.example.test/banner-nonaktif.png',
        'start_at' => now()->subDay(),
        'end_at' => now()->addMonth(),
        'is_active' => false,
    ]);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertSee('<option value="kategori-aktif-publik"', false)
        ->assertSee('<option value="Kota Unik Publik"', false)
        ->assertSee('https://cdn.example.test/banner-aktif.png')
        ->assertDontSee('https://cdn.example.test/banner-nonaktif.png');

    $this->get(route('faq'))
        ->assertOk()
        ->assertSee('FAQ aktif tampil?')
        ->assertDontSee('FAQ nonaktif tersembunyi?');

    $this->actingAs($admin)->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'slug' => $category->slug,
        'sort_order' => $category->sort_order,
        'is_active' => '0',
    ])->assertRedirect(route('admin.categories'));

    $this->actingAs($admin)->put(route('admin.venues.update', $venue), [
        'name' => $venue->name,
        'slug' => $venue->slug,
        'address' => $venue->address,
        'city' => $venue->city,
        'province' => $venue->province,
        'capacity' => $venue->capacity,
        'is_active' => '0',
    ])->assertRedirect(route('admin.venues'));

    $this->get(route('events.index'))
        ->assertOk()
        ->assertDontSee('<option value="kategori-aktif-publik"', false)
        ->assertDontSee('<option value="Kota Unik Publik"', false);
});

it('invalidates public caches when master data changes', function () {
    $admin = User::factory()->admin()->create();

    $this->get(route('home'))->assertOk();
    $this->get(route('events.index'))->assertOk();
    $this->get(route('faq'))->assertOk();

    expect(Cache::has(PublicCache::CATEGORIES))->toBeTrue()
        ->and(Cache::has(PublicCache::HOME_CATEGORIES))->toBeTrue()
        ->and(Cache::has(PublicCache::CITIES))->toBeTrue()
        ->and(Cache::has(PublicCache::FAQS))->toBeTrue()
        ->and(Cache::has(PublicCache::BANNERS))->toBeTrue();

    $this->actingAs($admin)->post(route('admin.categories.store'), [
        'name' => 'Kategori Cache',
        'is_active' => '1',
    ])->assertRedirect();

    expect(Cache::has(PublicCache::CATEGORIES))->toBeFalse()
        ->and(Cache::has(PublicCache::HOME_CATEGORIES))->toBeFalse()
        ->and(Cache::has(PublicCache::CITIES))->toBeTrue();

    $this->actingAs($admin)->post(route('admin.venues.store'), [
        'name' => 'Venue Cache',
        'address' => 'Jl. Cache',
        'city' => 'Jakarta',
        'province' => 'DKI Jakarta',
        'is_active' => '1',
    ])->assertRedirect();

    expect(Cache::has(PublicCache::CITIES))->toBeFalse();

    $this->actingAs($admin)->post(route('admin.faqs.store'), [
        'question' => 'FAQ cache?',
        'answer' => 'Ya.',
        'is_active' => '1',
    ])->assertRedirect();

    expect(Cache::has(PublicCache::FAQS))->toBeFalse();

    $this->actingAs($admin)->post(route('admin.banners.store'), [
        'image_url' => 'https://cdn.example.test/banner-cache.png',
        'is_active' => '1',
    ])->assertRedirect();

    expect(Cache::has(PublicCache::BANNERS))->toBeFalse();
});

it('restricts master data management to admins', function () {
    $organizer = User::factory()->organizer()->create();

    $this->actingAs($organizer)->get(route('admin.categories'))->assertForbidden();
    $this->actingAs($organizer)->get(route('admin.venues'))->assertForbidden();
    $this->actingAs($organizer)->get(route('admin.faqs'))->assertForbidden();
    $this->actingAs($organizer)->get(route('admin.banners'))->assertForbidden();

    $category = Category::factory()->create();
    $venue = Venue::factory()->create();

    $this->actingAs($organizer)->post(route('admin.categories.store'), [
        'name' => 'Tidak Boleh',
        'is_active' => '1',
    ])->assertForbidden();

    $this->actingAs($organizer)->put(route('admin.venues.update', $venue), [
        'name' => 'Tidak Boleh',
        'address' => 'Jl. Tidak',
        'city' => 'Jakarta',
        'province' => 'DKI Jakarta',
        'is_active' => '1',
    ])->assertForbidden();

    $this->actingAs($organizer)
        ->delete(route('admin.categories.destroy', $category))
        ->assertForbidden();

    expect(Category::find($category->getKey()))->not->toBeNull()
        ->and($venue->fresh()->name)->not->toBe('Tidak Boleh')
        ->and(AuditLog::query()->where('entity', 'category')->count())->toBe(0);
});
