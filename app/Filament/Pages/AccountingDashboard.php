<?php

namespace App\Filament\Pages;

use App\Services\AccountingService;
use App\Services\SettlementService;
use Carbon\Carbon;
use Filament\Pages\Page;

class AccountingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Financial Dashboard';

    protected static ?string $title = 'Financial Dashboard';

    protected static string $view = 'filament.pages.accounting-dashboard';

    public string $period = 'this_month';
    public string $customFrom = '';
    public string $customTo = '';

    public function mount(): void
    {
        $this->customFrom = now()->startOfMonth()->format('Y-m-d');
        $this->customTo = now()->format('Y-m-d');
    }

    public function updatedPeriod(): void
    {
        // Triggers re-render when period changes
    }

    protected function getDateRange(): array
    {
        return match ($this->period) {
            'last_month' => [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this_quarter' => [
                now()->startOfQuarter(),
                now(),
            ],
            'this_year' => [
                now()->startOfYear(),
                now(),
            ],
            'custom' => [
                Carbon::parse($this->customFrom ?: now()->startOfMonth()),
                Carbon::parse($this->customTo ?: now()),
            ],
            default => [ // this_month
                now()->startOfMonth(),
                now(),
            ],
        };
    }

    public function getDashboardStatsProperty(): array
    {
        [$from, $to] = $this->getDateRange();

        return app(AccountingService::class)->getDashboardStats($from, $to);
    }

    public function getRevenueTrendProperty(): array
    {
        [$from, $to] = $this->getDateRange();
        $days = $from->diffInDays($to);
        $granularity = $days > 90 ? 'monthly' : ($days > 30 ? 'weekly' : 'daily');

        return app(AccountingService::class)->getRevenueTrend($from, $to, $granularity);
    }

    public function getTopMerchantsProperty(): array
    {
        [$from, $to] = $this->getDateRange();

        return app(AccountingService::class)->getTopMerchants($from, $to, 5);
    }

    public function getPayablesSummaryProperty(): array
    {
        [$from, $to] = $this->getDateRange();

        return app(AccountingService::class)->getMerchantPayablesSummary($from, $to);
    }

    public function getTaxSummaryProperty(): array
    {
        [$from, $to] = $this->getDateRange();

        return app(AccountingService::class)->getTaxSummary($from, $to);
    }

    public function getNextPayoutDateProperty(): string
    {
        return app(SettlementService::class)->getNextPayoutDate()->format('M d, Y');
    }
}
