<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Order;
use App\Models\Product;
use App\Observers\OrderObserver;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);

        // Bust sitemap cache when products or brands change
        $bustSitemap = fn () => Cache::forget('sitemap_xml');
        Product::saved($bustSitemap);
        Product::deleted($bustSitemap);
        Brand::saved($bustSitemap);
        Brand::deleted($bustSitemap);

        // Share store settings with all frontend views
        View::composer('layouts.app', function ($view) {
            try {
                $settings = app(SettingsService::class);
                $logoPath = $settings->get('store_logo');
                $faviconPath = $settings->get('store_favicon');

                $view->with([
                    'storeName' => $settings->get('store_name', 'AD Perfumes'),
                    'storeLogo' => $logoPath ? Storage::url($logoPath) : null,
                    'storeFavicon' => $faviconPath ? Storage::url($faviconPath) : null,
                    // Tracking Pixels
                    'pixelMeta' => $settings->get('pixel_meta'),
                    'pixelTiktok' => $settings->get('pixel_tiktok'),
                    'pixelX' => $settings->get('pixel_x'),
                    'pixelSnapchat' => $settings->get('pixel_snapchat'),
                    'pixelPinterest' => $settings->get('pixel_pinterest'),
                    'pixelLinkedin' => $settings->get('pixel_linkedin'),
                    'pixelGoogleAnalytics' => $settings->get('pixel_google_analytics'),
                    'pixelGtm' => $settings->get('pixel_gtm'),
                    'pixelClarity' => $settings->get('pixel_clarity'),
                    // SEO
                    'googleSiteVerification' => $settings->get('google_site_verification'),
                ]);
            } catch (\Throwable $e) {
                $view->with([
                    'storeName' => 'AD Perfumes',
                    'storeLogo' => null,
                    'storeFavicon' => null,
                    'pixelMeta' => null,
                    'pixelTiktok' => null,
                    'pixelX' => null,
                    'pixelSnapchat' => null,
                    'pixelPinterest' => null,
                    'pixelLinkedin' => null,
                    'pixelGoogleAnalytics' => null,
                    'pixelGtm' => null,
                    'pixelClarity' => null,
                    'googleSiteVerification' => null,
                ]);
            }
        });
    }
}
