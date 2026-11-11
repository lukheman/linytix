<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'expires_at'], 'orders_status_expires_at_index');
            $table->index(['event_id', 'status'], 'orders_event_id_status_index');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->index(['status', 'start_at'], 'events_status_start_at_index');
            $table->index(['is_featured', 'status', 'start_at'], 'events_featured_status_start_index');
        });

        Schema::table('ticket_types', function (Blueprint $table) {
            $table->index(['event_id', 'is_active'], 'ticket_types_event_active_index');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['event_id', 'status'], 'tickets_event_id_status_index');
            $table->index('order_id', 'tickets_order_id_index');
        });

        Schema::table('check_ins', function (Blueprint $table) {
            $table->index(['event_id', 'scanned_at'], 'check_ins_event_scanned_at_index');
            $table->index('ticket_id', 'check_ins_ticket_id_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['order_id', 'status'], 'payments_order_id_status_index');
        });

        Schema::table('voucher_usages', function (Blueprint $table) {
            $table->index(['voucher_id', 'user_id'], 'voucher_usages_voucher_user_index');
        });
    }

    public function down(): void
    {
        // Indeks komposit yang diawali kolom FK menggantikan indeks otomatis
        // MySQL untuk foreign key. Buat ulang indeks FK sebelum menghapus
        // indeks komposit agar constraint tetap valid.
        Schema::table('orders', function (Blueprint $table) {
            $table->index('event_id', 'orders_event_id_foreign');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_event_id_status_index');
            $table->dropIndex('orders_status_expires_at_index');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_status_start_at_index');
            $table->dropIndex('events_featured_status_start_index');
        });

        Schema::table('ticket_types', function (Blueprint $table) {
            $table->index('event_id', 'ticket_types_event_id_foreign');
        });
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->dropIndex('ticket_types_event_active_index');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->index('event_id', 'tickets_event_id_foreign');
            $table->index('order_id', 'tickets_order_id_foreign');
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_event_id_status_index');
            $table->dropIndex('tickets_order_id_index');
        });

        Schema::table('check_ins', function (Blueprint $table) {
            $table->index('event_id', 'check_ins_event_id_foreign');
            $table->index('ticket_id', 'check_ins_ticket_id_foreign');
        });
        Schema::table('check_ins', function (Blueprint $table) {
            $table->dropIndex('check_ins_event_scanned_at_index');
            $table->dropIndex('check_ins_ticket_id_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('order_id', 'payments_order_id_foreign');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_order_id_status_index');
        });

        Schema::table('voucher_usages', function (Blueprint $table) {
            $table->index('voucher_id', 'voucher_usages_voucher_id_foreign');
        });
        Schema::table('voucher_usages', function (Blueprint $table) {
            $table->dropIndex('voucher_usages_voucher_user_index');
        });
    }
};
