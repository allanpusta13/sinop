<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\ActiveInTransitWidget;
use App\Filament\Widgets\LowStockAlertsWidget;
use App\Filament\Widgets\PendingFulfillmentWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentMovementsWidget;
use App\Filament\Widgets\SalesRevenueTrendWidget;
use App\Filament\Widgets\SalesVsPurchasesWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\TopSellingVariantsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Platform;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
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
            ->spa()
            ->profile(isSimple: false)
            ->colors([
                'primary' => Color::hex('#3b82f6'),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->navigationGroups([
                NavigationGroup::make('CATALOG')
                    ->label(__('navigation.groups.catalog'))
                    ->icon(Heroicon::CubeTransparent)
                    ->collapsible(),

                NavigationGroup::make('OPERATIONS')
                    ->label(__('navigation.groups.operations'))
                    ->icon(Heroicon::OutlinedRectangleStack)
                    ->collapsible(),

                NavigationGroup::make('PURCHASING')
                    ->label(__('navigation.groups.purchasing'))
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->collapsible(),

                NavigationGroup::make('SALES')
                    ->label(__('navigation.groups.sales'))
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->collapsible(),

                NavigationGroup::make('AUDIT LEDGERS')
                    ->label(__('navigation.groups.audit_ledgers'))
                    ->icon(Heroicon::QueueList)
                    ->collapsible(),

                NavigationGroup::make('SYSTEM ADMIN')
                    ->label(__('navigation.groups.system_admin'))
                    ->icon(Heroicon::BuildingOffice)
                    ->collapsible(false),
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                StatsOverviewWidget::class,
                LowStockAlertsWidget::class,
                RecentMovementsWidget::class,
                SalesRevenueTrendWidget::class,
                ActiveInTransitWidget::class,
                SalesVsPurchasesWidget::class,
                TopSellingVariantsWidget::class,
                PendingFulfillmentWidget::class,
                QuickActionsWidget::class,
            ])
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
            ->sidebarCollapsibleOnDesktop()

            ->sidebarWidth('280px')
            ->authMiddleware([
                Authenticate::class,
            ])
            ->maxContentWidth(Width::Full)
            ->strictAuthorization()
            ->databaseTransactions()
            ->collapsibleNavigationGroups(false)
            ->databaseNotifications()
            ->globalSearchFieldSuffix(fn (): ?string => match (Platform::detect()) {
                Platform::Windows, Platform::Linux => 'CTRL + K',
                Platform::Mac => '⌘ + K',
                default => null,
            });
    }
}
