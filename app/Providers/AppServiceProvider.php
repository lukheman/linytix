<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Models\SocialLink;
use App\Services\Payment\PaymentManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
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

        View::composer('components.layouts.public', function ($view): void {
            $view->with('socialLinks', SocialLink::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get());
        });
    }
}
