<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropUnique('webhook_events_gateway_external_id_unique');
            $table->unique(
                ['gateway', 'external_id', 'event_type'],
                'webhook_events_gateway_external_id_event_type_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropUnique('webhook_events_gateway_external_id_event_type_unique');
            $table->unique(['gateway', 'external_id'], 'webhook_events_gateway_external_id_unique');
        });
    }
};
