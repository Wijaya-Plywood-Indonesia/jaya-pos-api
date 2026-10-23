<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Products\ProductResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Jaya POS')
            // Logo bulat + nama cafe, warna logo mengikuti "Warna tema" cafe
            ->brandLogo(fn () => view('filament.brand', [
                'name' => auth()->user()?->cafe?->name ?? 'Jaya POS',
                'color' => $this->safeColor(auth()->user()?->cafe?->theme_color),
            ]))
            ->brandLogoHeight('2.25rem')
            ->colors([
                'primary' => '#d7263d',
            ])
            ->maxContentWidth('full')
            // Seperti mockup: langsung masuk ke Kelola Menu, tanpa dashboard
            ->homeUrl(fn () => ProductResource::getUrl('index'))
            ->navigationGroups([
                'Katalog',
                'Pengelolaan',
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => new HtmlString(
                    '<link rel="stylesheet" href="' . asset('css/jaya-admin.css')
                    . '?v=' . @filemtime(public_path('css/jaya-admin.css')) . '">'
                )
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    private function safeColor(?string $color): string
    {
        return $color && preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) ? $color : '#d7263d';
    }
}
