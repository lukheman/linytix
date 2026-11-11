<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('short_desc', 500)->nullable()->after('title');
            $table->text('terms')->nullable()->after('description');
            $table->string('poster_url', 2048)->nullable()->after('terms');
            $table->string('banner_url', 2048)->nullable()->after('poster_url');
            $table->timestamp('doors_open_at')->nullable()->after('start_at');
            $table->string('online_url', 2048)->nullable()->after('is_online');
            $table->string('timezone', 64)->default('Asia/Jakarta')->after('online_url');
            $table->boolean('is_featured')->default(false)->after('timezone');
            $table->boolean('require_identity')->default(false)->after('is_featured');
            $table->unsignedBigInteger('view_count')->default(0)->after('require_identity');
            $table->timestamp('published_at')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
        });

        Schema::table('ticket_types', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->unsignedInteger('min_per_order')->default(1)->after('sold_count');
            $table->unsignedInteger('max_per_order')->default(4)->after('min_per_order');
            $table->timestamp('sale_start_at')->nullable()->after('max_per_order');
            $table->timestamp('sale_end_at')->nullable()->after('sale_start_at');
        });

        // Backfill nilai default untuk data lama.
        DB::table('events')->whereNull('timezone')->update(['timezone' => 'Asia/Jakarta']);
        DB::table('events')
            ->where('status', 'published')
            ->whereNull('published_at')
            ->update(['published_at' => now()]);
        DB::table('ticket_types')->whereNull('min_per_order')->update(['min_per_order' => 1]);
        DB::table('ticket_types')->whereNull('max_per_order')->update(['max_per_order' => 4]);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
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
            ]);
        });

        Schema::table('ticket_types', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'min_per_order',
                'max_per_order',
                'sale_start_at',
                'sale_end_at',
            ]);
        });
    }
};
