<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('organizers')
            ->select('owner_user_id')
            ->groupBy('owner_user_id')
            ->havingRaw('count(*) > 1')
            ->pluck('owner_user_id');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Tidak dapat menambahkan unique index organizers.owner_user_id: '
                .'owner berikut memiliki lebih dari satu organizer: '.$duplicates->implode(', ')
            );
        }

        Schema::table('organizers', function (Blueprint $table) {
            $table->unique('owner_user_id');
        });
    }

    public function down(): void
    {
        // MySQL/MariaDB dapat memakai unique index ini untuk foreign key
        // owner_user_id, sehingga index biasa perlu dipastikan ada lebih dulu.
        $hasOwnerIndex = collect(Schema::getIndexes('organizers'))
            ->contains(fn (array $index): bool => $index['columns'] === ['owner_user_id']
                && ! $index['unique']);

        if (! $hasOwnerIndex) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->index('owner_user_id');
            });
        }

        Schema::table('organizers', function (Blueprint $table) {
            $table->dropUnique(['owner_user_id']);
        });
    }
};
