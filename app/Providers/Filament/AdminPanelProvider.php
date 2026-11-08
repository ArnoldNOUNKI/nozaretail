<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
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
            ->brandName('Noza Retail')
            ->brandLogo(asset('brand/logo.png'))
            ->darkModeBrandLogo(asset('brand/logo-white.png'))
            ->brandLogoHeight('3.25rem')
            ->favicon(asset('apple-touch-icon.png'))
            ->font('Instrument Sans')
            ->colors([
                'primary' => Color::hex('#1c1c1c'),
                'gray' => Color::Neutral,
                'success' => Color::hex('#1f7a52'),
                'warning' => Color::Amber,
                'danger' => Color::hex('#c0352b'),
                'info' => Color::hex('#2f5fd0'),
            ])
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->navigationGroups([
                NavigationGroup::make('Ventes'),
                NavigationGroup::make('Catalogue'),
                NavigationGroup::make('Clients'),
                NavigationGroup::make('Marketing'),
                NavigationGroup::make('Configuration')->collapsed(),
            ])
            ->navigationItems([
                NavigationItem::make('Voir la boutique')
                    ->url('/', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->sort(100),
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => Blade::render('<style>.fi-logo{filter:none}.fi-sidebar-header{border-bottom:1px solid rgb(0 0 0 / .06)}</style>'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
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
            ->authMiddleware([Authenticate::class]);
    }
}
