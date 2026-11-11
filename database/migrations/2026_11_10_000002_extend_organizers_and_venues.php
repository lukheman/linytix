<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->string('logo_url')->nullable()->after('description');
            $table->string('contact_email')->nullable()->after('logo_url');
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->string('bank_name')->nullable()->after('contact_phone');
            $table->string('bank_account_no')->nullable()->after('bank_name');
            $table->string('bank_account_name')->nullable()->after('bank_account_no');
            $table->timestamp('verified_at')->nullable()->after('is_verified');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            $table->text('verification_note')->nullable()->after('verified_by');
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('province');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('maps_url', 2048)->nullable()->after('longitude');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('icon_url', 2048)->nullable()->after('slug');
            $table->smallInteger('sort_order')->default(0)->after('icon_url');
        });

        // Backfill organizer yang sudah terverifikasi.
        DB::table('organizers')
            ->where('is_verified', true)
            ->whereNull('verified_at')
            ->update(['verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'logo_url',
                'contact_email',
                'contact_phone',
                'bank_name',
                'bank_account_no',
                'bank_account_name',
                'verified_at',
                'verified_by',
                'verification_note',
            ]);
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'maps_url']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['icon_url', 'sort_order']);
        });
    }
};
