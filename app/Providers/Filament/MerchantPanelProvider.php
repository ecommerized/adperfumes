<?php

namespace App\Providers\Filament;

use App\Filament\Merchant\Pages\Auth\Register;
use App\Http\Middleware\CheckMerchantApproved;
use App\Services\SettingsService;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class MerchantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Get logo, favicon and store name from settings
        $settings = app(SettingsService::class);
        $logoPath = $settings->get('store_logo');
        $faviconPath = $settings->get('store_favicon');
        $storeName = $settings->get('store_name', 'AD Perfumes');

        $panel = $panel
            ->id('merchant')
            ->path('merchant')
            ->login()
            ->registration(Register::class)
            ->brandName($storeName . ' - Merchant Panel')
            ->authGuard('merchant')
            ->colors([
                'primary' => Color::hex('#C9A96E'),
            ])
            ->discoverResources(in: app_path('Filament/Merchant/Resources'), for: 'App\\Filament\\Merchant\\Resources')
            ->discoverPages(in: app_path('Filament/Merchant/Pages'), for: 'App\\Filament\\Merchant\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Merchant/Widgets'), for: 'App\\Filament\\Merchant\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                CheckMerchantApproved::class,
            ]);

        // Set brand logo
        if ($logoPath) {
            $panel->brandLogo(Storage::url($logoPath))
                ->brandLogoHeight('3rem');
        }

        // Set favicon
        if ($faviconPath) {
            $panel->favicon(Storage::url($faviconPath));
        }

        return $panel;
    }
}
