<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('order_number')->unique();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignUuid('event_id')->constrained();
            $t->foreignUuid('voucher_id')->nullable()->constrained()->nullOnDelete();
            $t->string('buyer_name');
            $t->string('buyer_email');
            $t->string('buyer_phone')->nullable();
            foreach (['subtotal', 'discount_amount', 'admin_fee', 'payment_fee', 'tax_amount', 'total_amount'] as $c) {
                $t->unsignedBigInteger($c)->default(0);
            }$t->string('status')->default('pending');
            $t->timestampTz('expires_at')->nullable();
            $t->timestampTz('paid_at')->nullable();
            $t->timestamps();
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $t->foreignUuid('ticket_type_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->unsignedBigInteger('unit_price');
            $t->unsignedBigInteger('subtotal');
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $t->string('gateway');
            $t->string('method');
            $t->string('gateway_ref')->unique()->nullable();
            $t->unsignedBigInteger('amount');
            $t->string('status')->default('pending');
            $t->string('payment_url')->nullable();
            $t->json('raw_response')->nullable();
            $t->timestampTz('paid_at')->nullable();
            $t->timestamps();
        });
        Schema::create('tickets', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('ticket_code')->unique();
            $t->foreignUuid('order_item_id')->constrained();
            $t->foreignUuid('order_id')->constrained();
            $t->foreignUuid('event_id')->constrained();
            $t->foreignUuid('ticket_type_id')->constrained();
            $t->string('holder_name');
            $t->string('holder_id_number')->nullable();
            $t->string('status')->default('active');
            $t->timestampTz('issued_at')->nullable();
            $t->timestampTz('used_at')->nullable();
            $t->timestamps();
        });
        Schema::create('check_ins', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('ticket_id')->constrained();
            $t->foreignUuid('event_id')->constrained();
            $t->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('gate')->nullable();
            $t->string('result');
            $t->timestampTz('scanned_at')->useCurrent();
            $t->timestamps();
        });
        Schema::create('event_staff', function (Blueprint $t) {
            $t->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->primary(['event_id', 'user_id']);
        });
        Schema::create('wishlists', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $t->timestampTz('created_at')->useCurrent();
            $t->primary(['user_id', 'event_id']);
        });
        Schema::create('banners', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('event_id')->nullable()->constrained()->nullOnDelete();
            $t->string('image_url');
            $t->string('link_url')->nullable();
            $t->smallInteger('sort_order')->default(0);
            $t->timestampTz('start_at')->nullable();
            $t->timestampTz('end_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('faqs', function (Blueprint $t) {
            $t->id();
            $t->text('question');
            $t->text('answer');
            $t->smallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('contact_messages', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->string('email');
            $t->string('phone')->nullable();
            $t->string('subject');
            $t->text('message');
            $t->string('status')->default('new');
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->string('entity');
            $t->string('entity_id')->nullable();
            $t->json('meta')->nullable();
            $t->ipAddress('ip_address')->nullable();
            $t->timestamps();
        });
        Schema::create('voucher_usages', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('voucher_id')->constrained()->cascadeOnDelete();
            $t->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestampTz('used_at')->useCurrent();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['voucher_usages', 'audit_logs', 'contact_messages', 'faqs', 'banners', 'wishlists', 'event_staff', 'check_ins', 'tickets', 'payments', 'order_items', 'orders'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
