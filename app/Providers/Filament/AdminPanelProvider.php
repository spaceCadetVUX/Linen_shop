<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetAdminLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
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
            ->brandName(env('APP_NAME', 'Backbone'))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->collapsedSidebarWidth('4rem')
            ->maxContentWidth(Width::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make(__('admin.nav.catalog')),
                NavigationGroup::make(__('admin.nav.commerce')),
                NavigationGroup::make(__('admin.nav.blog')),
                NavigationGroup::make(__('admin.nav.content')),
                NavigationGroup::make(__('admin.nav.seo_geo')),
                NavigationGroup::make(__('admin.nav.setting')),
                NavigationGroup::make(__('admin.nav.system')),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetAdminLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authenticatedRoutes(function (): void {
                // Admin locale switcher — remembered in session (no /vi|/en prefix here,
                // unlike the storefront). See SetAdminLocale + locale-switcher.blade.php.
                Route::get('locale/{locale}', function (string $locale, Request $request) {
                    if (in_array($locale, config('app.supported_locales'), true)) {
                        $request->session()->put('admin_locale', $locale);
                    }

                    return redirect()->back();
                })->name('locale.switch');
            })
            ->renderHook(
                'panels::head.end',
                fn (): HtmlString => new HtmlString(
                    view('filament.reorder-styles')->render()
                ),
            )
            ->renderHook(
                'panels::topbar.end',
                fn (): HtmlString => new HtmlString(
                    view('filament.locale-switcher')->render()
                ),
            );
    }
}
