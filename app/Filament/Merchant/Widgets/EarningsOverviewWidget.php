<?php

namespace App\Filament\Merchant\Widgets;

use App\Models\Settlement;
use App\Services\SettlementService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EarningsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $merchantId = auth('merchant')->id();

        $totalEarnings = Settlement::where('merchant_id', $merchantId)
            ->where('status', 'paid')
            ->sum('merchant_payout');

        $pendingSettlement = Settlement::where('merchant_id', $merchantId)
            ->where('status', 'pending')
            ->sum('merchant_payout');

        $totalOrders = \App\Models\OrderItem::where('merchant_id', $merchantId)
            ->count();

        $nextPayoutDate = app(SettlementService::class)->getNextPayoutDate();

        return [
            Stat::make('Total Earnings', 'AED ' . number_format($totalEarnings, 2))
                ->description('All settled payouts')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Pending Settlement', 'AED ' . number_format($pendingSettlement, 2))
                ->description('Awaiting payment')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Orders', number_format($totalOrders))
                ->description('All order items')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Next Payout', $nextPayoutDate->format('M d, Y'))
                ->description('Upcoming payout date')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}
