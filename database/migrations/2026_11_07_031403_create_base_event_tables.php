<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizers', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('owner_user_id')->constrained('users');
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->boolean('is_verified')->default(false);
            $t->timestamps();
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->smallIncrements('id');
            $t->string('name')->unique();
            $t->string('slug')->unique();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('venues', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->text('address');
            $t->string('city');
            $t->string('province');
            $t->unsignedInteger('capacity')->nullable();
            $t->timestamps();
        });
        Schema::create('events', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('organizer_id')->constrained();
            $t->unsignedSmallInteger('category_id');
            $t->foreign('category_id')->references('id')->on('categories');
            $t->foreignUuid('venue_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->boolean('is_online')->default(false);
            $t->timestamp('start_at');
            $t->timestamp('end_at');
            $t->unsignedBigInteger('min_price')->default(0);
            $t->string('status')->default('draft');
            $t->timestamps();
        });
        Schema::create('event_images', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $t->string('image_url');
            $t->string('caption')->nullable();
            $t->smallInteger('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('event_lineups', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('photo_url')->nullable();
            $t->timestamp('perform_at')->nullable();
            $t->smallInteger('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('ticket_types', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unsignedBigInteger('price');
            $t->unsignedInteger('quota');
            $t->unsignedInteger('sold_count')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('vouchers', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('code')->unique();
            $t->foreignUuid('event_id')->nullable()->constrained()->nullOnDelete();
            $t->string('discount_type');
            $t->unsignedBigInteger('discount_value');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['vouchers', 'ticket_types', 'event_lineups', 'event_images', 'events', 'venues', 'categories', 'organizers'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
