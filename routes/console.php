<?php

use App\Jobs\ExpireOrders;
use App\Jobs\ReconcilePayments;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpireOrders)->everyMinute()->withoutOverlapping();
Schedule::job(new ReconcilePayments)->everyFiveMinutes()->withoutOverlapping();
