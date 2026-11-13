<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('capacity');
        });

        $used = [];

        DB::table('venues')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function (object $venue) use (&$used): void {
                $base = Str::slug((string) $venue->name) ?: 'venue';
                $slug = $base;
                $counter = 2;

                while (in_array($slug, $used, true)) {
                    $slug = $base.'-'.$counter;
                    $counter++;
                }

                $used[] = $slug;

                DB::table('venues')
                    ->where('id', $venue->id)
                    ->update(['slug' => $slug]);
            });

        Schema::table('venues', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'is_active']);
        });
    }
};
