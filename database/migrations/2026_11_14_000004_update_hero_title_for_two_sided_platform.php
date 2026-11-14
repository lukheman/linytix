<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SiteSetting::query()
            ->where('key', 'hero_title')
            ->update(['value' => 'Temukan event di seluruh Indonesia, beli tiketnya, atau daftarkan eventmu.']);
    }

    public function down(): void
    {
        SiteSetting::query()
            ->where('key', 'hero_title')
            ->update(['value' => 'Temukan event pilihan di seluruh Indonesia.']);
    }
};
