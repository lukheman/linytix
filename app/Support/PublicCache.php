<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class PublicCache
{
    public const CATEGORIES = 'public.master.categories';

    public const HOME_CATEGORIES = 'public.master.home-categories';

    public const CITIES = 'public.master.cities';

    public const FAQS = 'public.master.faqs';

    public const BANNERS = 'public.master.banners';

    public const TTL_SECONDS = 900;

    public static function remember(string $key, Closure $callback): mixed
    {
        return Cache::remember($key, now()->addSeconds(self::TTL_SECONDS), $callback);
    }

    public static function forgetCategories(): void
    {
        Cache::forget(self::CATEGORIES);
        Cache::forget(self::HOME_CATEGORIES);
    }

    public static function forgetCities(): void
    {
        Cache::forget(self::CITIES);
    }

    public static function forgetFaqs(): void
    {
        Cache::forget(self::FAQS);
    }

    public static function forgetBanners(): void
    {
        Cache::forget(self::BANNERS);
    }
}
