<?php

namespace App\Filament\Widgets;

use App\Services\AccountingService;
use Filament\Widgets\ChartWidget;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Commission Revenue (Last 30 Days)';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $service = app(AccountingService::class);
        $trend = $service->getRevenueTrend(now()->subDays(30), now(), 'daily');

        $labels = array_map(function ($date) {
            return \Carbon\Carbon::parse($date)->format('M d');
        }, array_keys($trend));

        return [
            'datasets' => [
                [
                    'label' => 'Commission (AED)',
                    'data' => array_values($trend),
                    'borderColor' => '#C9A96E',
                    'backgroundColor' => 'rgba(201, 169, 110, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
