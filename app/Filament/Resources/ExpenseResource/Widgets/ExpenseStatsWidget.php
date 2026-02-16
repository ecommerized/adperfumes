<?php

namespace App\Filament\Resources\ExpenseResource\Widgets;

use App\Models\Expense;
use App\Services\ExpenseService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpenseStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $expenseService = app(ExpenseService::class);

        $currentMonth = now()->format('Y-m');
        $monthStart = Carbon::parse($currentMonth . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $ytdStart = now()->startOfYear();
        $ytdEnd = now()->endOfDay();

        // Monthly totals
        $monthlyTotal = $expenseService->getTotalExpenses(
            $monthStart->toDateString(),
            $monthEnd->toDateString()
        );

        // YTD totals
        $ytdTotal = $expenseService->getTotalExpenses(
            $ytdStart->toDateString(),
            $ytdEnd->toDateString()
        );

        // Pending approval
        $pendingCount = Expense::where('status', 'pending_approval')->count();
        $pendingAmount = Expense::where('status', 'pending_approval')->sum('total_amount');

        // Input VAT reclaimable (current month)
        $inputVatData = $expenseService->getInputVatReclaimable(
            $monthStart->toDateString(),
            $monthEnd->toDateString()
        );

        return [
            Stat::make('Monthly Expenses', 'AED ' . number_format($monthlyTotal, 2))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),

            Stat::make('YTD Expenses', 'AED ' . number_format($ytdTotal, 2))
                ->description('Year to date')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('success'),

            Stat::make('Pending Approval', $pendingCount)
                ->description('AED ' . number_format($pendingAmount, 2))
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingCount > 0 ? 'warning' : 'gray'),

            Stat::make('Input VAT (MTD)', 'AED ' . number_format($inputVatData['input_vat_reclaimable'], 2))
                ->description('Reclaimable this month')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('info'),
        ];
    }
}
