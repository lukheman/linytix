<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Services\Payment\PaymentManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, function ($app): PaymentGateway {
            return $app->make(PaymentManager::class)->gateway();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        $this->configureRateLimiting();
    }

    /**
     * Daftarkan limiter bernama agar batas dapat diatur lewat config/env.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('checkout-store', fn (Request $request) => Limit::perMinute(
            max(1, (int) config('ticketing.rate_limits.checkout_store', 10))
        )->by($request->ip()));

        RateLimiter::for('checkout-pay', fn (Request $request) => Limit::perMinute(
            max(1, (int) config('ticketing.rate_limits.checkout_pay', 10))
        )->by($request->ip()));

        RateLimiter::for('checkout-status', fn (Request $request) => Limit::perMinute(
            max(1, (int) config('ticketing.rate_limits.checkout_status', 120))
        )->by($request->ip()));

        RateLimiter::for('ticket-scan', fn (Request $request) => Limit::perMinute(
            max(1, (int) config('ticketing.rate_limits.ticket_scan', 60))
        )->by($request->ip()));

        RateLimiter::for('payment-webhook', fn (Request $request) => Limit::perMinute(
            max(1, (int) config('ticketing.rate_limits.payment_webhook', 120))
        )->by($request->ip().'|'.$request->route('gateway')));
    }
}
