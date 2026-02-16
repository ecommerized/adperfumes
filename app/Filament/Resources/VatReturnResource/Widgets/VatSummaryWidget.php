<?php

namespace App\Filament\Resources\VatReturnResource\Widgets;

use App\Models\VatReturn;
use App\Services\VatReturnService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VatSummaryWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $service = app(VatReturnService::class);

        // Current quarter
        $currentQuarter = now()->quarter;
        $currentYear = now()->year;

        $currentReturn = VatReturn::where('year', $currentYear)
            ->where('quarter', $currentQuarter)
            ->where('period_type', 'quarterly')
            ->first();

        // YTD Summary
        $ytdStart = now()->startOfYear();
        $ytdEnd = now()->endOfDay();
        $ytdSummary = $service->getVatSummary($ytdStart, $ytdEnd);

        // Pending and overdue
        $pending = VatReturn::where('status', 'pending_review')->count();
        $overdue = VatReturn::overdue()->count();

        return [
            Stat::make('Current Quarter VAT', $currentReturn
                ? 'AED ' . number_format($currentReturn->net_vat_payable, 2)
                : 'Not Prepared'
            )
                ->description("Q{$currentQuarter} {$currentYear} - " . ($currentReturn?->status ?? 'N/A'))
                ->descriptionIcon('heroicon-o-calendar')
                ->color($currentReturn && $currentReturn->net_vat_payable < 0 ? 'success' : 'danger'),

            Stat::make('YTD Output VAT', 'AED ' . number_format($ytdSummary['output_vat_amount'], 2))
                ->description('VAT collected from sales')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('info'),

            Stat::make('YTD Input VAT', 'AED ' . number_format($ytdSummary['input_vat_reclaimable'], 2))
                ->description('VAT reclaimable from expenses')
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color('success'),

            Stat::make('Status', $overdue > 0 ? "$overdue Overdue" : ($pending > 0 ? "$pending Pending" : 'Up to Date'))
                ->description($overdue > 0 ? 'Returns past filing deadline' : 'Returns awaiting review')
                ->descriptionIcon($overdue > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-clock')
                ->color($overdue > 0 ? 'danger' : ($pending > 0 ? 'warning' : 'success')),
        ];
    }
}
