<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['order_id', 'ticket_type_id'], 'order_items_order_ticket_type_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_items')) {
            return;
        }

        $indexes = collect(Schema::getIndexes('order_items'))->pluck('name');

        // MySQL/MariaDB dapat memakai composite index untuk FK order_id dan
        // menghapus index tunggalnya. Pastikan index FK tersedia sebelum
        // composite dihapus agar rollback tidak gagal.
        if (! $indexes->contains('order_items_order_id_foreign')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->index('order_id', 'order_items_order_id_foreign');
            });
        }

        if ($indexes->contains('order_items_order_ticket_type_index')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropIndex('order_items_order_ticket_type_index');
            });
        }
    }
};
