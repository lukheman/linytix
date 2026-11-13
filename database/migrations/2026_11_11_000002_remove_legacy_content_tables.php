<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'rental_items',
            'services',
            'collaboration_photos',
            'collaborations',
            'testimonials',
            'social_links',
            'team_members',
            'contact_messages',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Legacy content intentionally removed. Restore from database backup if needed.
    }
};
