<?php

namespace App\Filament\Widgets;

use App\Services\AccountingService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $service = app(AccountingService::class);
        $stats = $service->getDashboardStats(now()->startOfMonth(), now());

        return [
            Stat::make('GMV', 'AED ' . number_format($stats['gmv'], 2))
                ->description($stats['order_count'] . ' paid orders')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),

            Stat::make('Commission Revenue', 'AED ' . number_format($stats['commission_revenue'], 2))
                ->description('From settlements')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Refunds Issued', 'AED ' . number_format($stats['total_refunds'], 2))
                ->description('This month')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('danger'),

            Stat::make('Net Revenue', 'AED ' . number_format($stats['net_revenue'], 2))
                ->description('Commission - Reversals')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),

            Stat::make('VAT Collected', 'AED ' . number_format($stats['tax_collected'], 2))
                ->description('Output VAT 5%')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('warning'),

            Stat::make('Pending Payables', 'AED ' . number_format($stats['pending_payables'], 2))
                ->description($stats['settled_count'] . ' settlements paid')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Accounting';
    }
}
