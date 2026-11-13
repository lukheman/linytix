<?php

use Illuminate\Support\Facades\Schema;

it('has the stage 0 schema extensions', function () {
    foreach (['roles', 'role_user', 'refunds', 'webhook_events', 'notification_logs'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Tabel {$table} tidak ada.");
    }

    expect(Schema::hasColumns('users', ['phone', 'google_id', 'is_active', 'last_login_at']))->toBeTrue();
    expect(Schema::hasColumns('organizers', ['logo_url', 'contact_email', 'verified_at', 'verified_by', 'verification_note']))->toBeTrue();
    expect(Schema::hasColumns('venues', ['latitude', 'longitude', 'maps_url']))->toBeTrue();
    expect(Schema::hasColumns('categories', ['icon_url', 'sort_order']))->toBeTrue();
    expect(Schema::hasColumns('events', [
        'short_desc',
        'terms',
        'poster_url',
        'banner_url',
        'doors_open_at',
        'online_url',
        'timezone',
        'is_featured',
        'require_identity',
        'view_count',
        'published_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ]))->toBeTrue();
    expect(Schema::hasColumns('ticket_types', ['description', 'min_per_order', 'max_per_order', 'sale_start_at', 'sale_end_at']))->toBeTrue();
    expect(Schema::hasColumns('payments', ['expires_at']))->toBeTrue();
    expect(Schema::hasColumns('vouchers', ['max_discount', 'min_purchase', 'usage_limit', 'per_user_limit', 'used_count', 'valid_from', 'valid_until']))->toBeTrue();
});

it('has composite indexes for ticketing queries', function () {
    expect(Schema::hasIndex('orders', 'orders_status_expires_at_index'))->toBeTrue();
    expect(Schema::hasIndex('events', 'events_status_start_at_index'))->toBeTrue();
    expect(Schema::hasIndex('ticket_types', 'ticket_types_event_active_index'))->toBeTrue();
    expect(Schema::hasIndex('tickets', 'tickets_event_id_status_index'))->toBeTrue();
    expect(Schema::hasIndex('check_ins', 'check_ins_event_scanned_at_index'))->toBeTrue();
    expect(Schema::hasIndex('payments', 'payments_order_id_status_index'))->toBeTrue();
    expect(Schema::hasIndex('voucher_usages', 'voucher_usages_voucher_user_index'))->toBeTrue();
    expect(Schema::hasIndex('organizers', ['owner_user_id'], 'unique'))->toBeTrue();
});
