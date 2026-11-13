<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabel legacy bisa saja sudah dihapus lebih dulu (mis. rollback parsial
        // atau database yang dibangun dari schema.sql terbaru).
        if (! Schema::hasTable('rental_items')) {
            return;
        }

        $hasCategory = Schema::hasColumn('rental_items', 'category');
        $hasActive = Schema::hasColumn('rental_items', 'is_active');

        if ($hasCategory && $hasActive) {
            return;
        }

        Schema::table('rental_items', function (Blueprint $table) use ($hasCategory, $hasActive) {
            if (! $hasCategory) {
                $table->string('category')->nullable()->after('service_id');
            }

            if (! $hasActive) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migration remove_legacy_content_tables menghapus rental_items dan
        // sengaja tidak membuatnya kembali, sehingga rollback harus aman
        // ketika tabel atau kolom sudah tidak ada.
        if (! Schema::hasTable('rental_items')) {
            return;
        }

        $columns = array_values(array_filter(
            ['category', 'is_active'],
            fn (string $column): bool => Schema::hasColumn('rental_items', $column),
        ));

        if ($columns === []) {
            return;
        }

        Schema::table('rental_items', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};
