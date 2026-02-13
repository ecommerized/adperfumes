<?php

namespace App\Filament\Pages;

use App\Services\SearchConsoleService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class SearchConsoleDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Search Console';

    protected static ?string $title = 'Search Console Analytics';

    protected static string $view = 'filament.pages.search-console-dashboard';

    public string $dateRange = '28';
    public string $activeTab = 'queries';

    public function getIsConfiguredProperty(): bool
    {
        $service = app(SearchConsoleService::class);

        return $service->isConfigured() && $service->getSiteUrl();
    }

    public function getSummaryProperty(): array
    {
        if (!$this->getIsConfiguredProperty()) {
            return [];
        }

        $service = app(SearchConsoleService::class);
        $endDate = now()->subDays(3)->format('Y-m-d');
        $startDate = now()->subDays((int) $this->dateRange + 3)->format('Y-m-d');

        return $service->getSummary($startDate, $endDate);
    }

    public function getQueryDataProperty(): array
    {
        if (!$this->getIsConfiguredProperty()) {
            return [];
        }

        $service = app(SearchConsoleService::class);
        $endDate = now()->subDays(3)->format('Y-m-d');
        $startDate = now()->subDays((int) $this->dateRange + 3)->format('Y-m-d');

        return $service->getSearchAnalytics($startDate, $endDate, 'query', 25);
    }

    public function getPageDataProperty(): array
    {
        if (!$this->getIsConfiguredProperty()) {
            return [];
        }

        $service = app(SearchConsoleService::class);
        $endDate = now()->subDays(3)->format('Y-m-d');
        $startDate = now()->subDays((int) $this->dateRange + 3)->format('Y-m-d');

        return $service->getSearchAnalytics($startDate, $endDate, 'page', 25);
    }

    public function refreshData(): void
    {
        $endDate = now()->subDays(3)->format('Y-m-d');
        $startDate = now()->subDays((int) $this->dateRange + 3)->format('Y-m-d');

        Cache::forget("gsc_summary_{$startDate}_{$endDate}");
        Cache::forget("gsc_analytics_query_{$startDate}_{$endDate}_25");
        Cache::forget("gsc_analytics_page_{$startDate}_{$endDate}_25");

        $this->dispatch('$refresh');
    }
}
