<x-filament-panels::page>
    {{-- Period Selector --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3 flex-wrap">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Period:</label>
            <select wire:model.live="period" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500">
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
                <option value="this_quarter">This Quarter</option>
                <option value="this_year">This Year</option>
                <option value="custom">Custom Range</option>
            </select>

            @if($period === 'custom')
                <input type="date" wire:model.live="customFrom"
                    class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500">
                <span class="text-gray-400">to</span>
                <input type="date" wire:model.live="customTo"
                    class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500">
            @endif
        </div>
        <div class="text-xs text-gray-400">
            Next Payout: {{ $this->getNextPayoutDateProperty() }}
        </div>
    </div>

    {{-- Key Metrics --}}
    @php $stats = $this->getDashboardStatsProperty(); @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <p class="text-2xl font-bold text-green-600">AED {{ number_format($stats['gmv'], 2) }}</p>
                <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">GMV</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $stats['order_count'] }} orders</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-2xl font-bold text-primary-600">AED {{ number_format($stats['commission_revenue'], 2) }}</p>
                <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Commission</p>
                <p class="text-xs text-gray-400 mt-0.5">From settlements</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-2xl font-bold text-red-500">AED {{ number_format($stats['total_refunds'], 2) }}</p>
                <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Refunds</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-2xl font-bold text-emerald-600">AED {{ number_format($stats['net_revenue'], 2) }}</p>
                <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Net Revenue</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-2xl font-bold text-amber-600">AED {{ number_format($stats['tax_collected'], 2) }}</p>
                <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">VAT Collected</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-600">AED {{ number_format($stats['pending_payables'], 2) }}</p>
                <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Pending Payables</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $stats['settled_count'] }} settled</p>
            </div>
        </x-filament::section>
    </div>

    {{-- Revenue Trend Chart --}}
    @php $trend = $this->getRevenueTrendProperty(); @endphp

    <x-filament::section>
        <x-slot name="heading">Commission Revenue Trend</x-slot>
        <div class="h-64">
            <canvas id="revenueTrendChart"></canvas>
        </div>
    </x-filament::section>

    {{-- Two Column Layout: Top Merchants & Payables --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Top Merchants --}}
        <x-filament::section>
            <x-slot name="heading">Top Merchants</x-slot>
            @php $topMerchants = $this->getTopMerchantsProperty(); @endphp

            @if(empty($topMerchants))
                <p class="text-gray-500 text-sm text-center py-4">No merchant data for this period.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-gray-400">#</th>
                                <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-gray-400">Merchant</th>
                                <th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-gray-400">Orders</th>
                                <th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-gray-400">GMV</th>
                                <th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-gray-400">Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topMerchants as $index => $merchant)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2 px-2 text-gray-400 text-xs">{{ $index + 1 }}</td>
                                    <td class="py-2 px-2 font-medium">{{ $merchant->business_name }}</td>
                                    <td class="py-2 px-2 text-right">{{ $merchant->order_count }}</td>
                                    <td class="py-2 px-2 text-right">AED {{ number_format($merchant->gmv, 2) }}</td>
                                    <td class="py-2 px-2 text-right text-primary-600">AED {{ number_format($merchant->commission, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        {{-- Pending Payables --}}
        <x-filament::section>
            <x-slot name="heading">Pending Merchant Payables</x-slot>
            @php $payables = $this->getPayablesSummaryProperty(); @endphp

            @if(empty($payables))
                <p class="text-gray-500 text-sm text-center py-4">No pending payables.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-gray-400">Merchant</th>
                                <th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-gray-400">Pending</th>
                                <th class="text-right py-2 px-2 font-medium text-gray-600 dark:text-gray-400">Orders</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payables as $payable)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2 px-2 font-medium">{{ $payable->business_name }}</td>
                                    <td class="py-2 px-2 text-right font-medium text-amber-600">AED {{ number_format($payable->pending_payout, 2) }}</td>
                                    <td class="py-2 px-2 text-right">{{ $payable->eligible_orders }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- Tax Summary --}}
    @php $tax = $this->getTaxSummaryProperty(); @endphp

    <x-filament::section class="mt-6">
        <x-slot name="heading">UAE VAT Summary</x-slot>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <p class="text-lg font-bold text-gray-700 dark:text-gray-300">AED {{ number_format($tax['total_sales_incl_tax'], 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">Sales (Incl. VAT)</p>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <p class="text-lg font-bold text-amber-600">AED {{ number_format($tax['output_vat'], 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">Output VAT</p>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <p class="text-lg font-bold text-gray-700 dark:text-gray-300">AED {{ number_format($tax['commission_vat'], 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">Commission VAT</p>
            </div>
            <div class="text-center p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-800">
                <p class="text-lg font-bold text-primary-600">AED {{ number_format($tax['net_vat_payable'], 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">Net VAT Payable</p>
            </div>
        </div>
    </x-filament::section>

    {{-- Chart.js Script --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueTrendChart');
            if (!ctx) return;

            const data = @json($trend);
            const labels = Object.keys(data);
            const values = Object.values(data);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels.map(d => {
                        const date = new Date(d + 'T00:00:00');
                        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    }),
                    datasets: [{
                        label: 'Commission (AED)',
                        data: values,
                        borderColor: '#C9A96E',
                        backgroundColor: 'rgba(201, 169, 110, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 2,
                        pointHoverRadius: 5,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => 'AED ' + value.toLocaleString()
                            }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</x-filament-panels::page>
