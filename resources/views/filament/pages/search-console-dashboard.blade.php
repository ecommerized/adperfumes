<x-filament-panels::page>
    @if(!$this->getIsConfiguredProperty())
        <x-filament::section>
            <div class="text-center py-8">
                <div class="flex justify-center mb-4">
                    <x-heroicon-o-exclamation-triangle class="w-12 h-12 text-warning-500" />
                </div>
                <h3 class="text-lg font-semibold mb-2">Google Search Console Not Configured</h3>
                <p class="text-sm text-gray-500 mb-4">
                    You need to set up your Google Search Console credentials before viewing analytics.
                </p>
                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Pages\SearchConsoleSettings::getUrl() }}"
                >
                    Go to Search Console Settings
                </x-filament::button>
            </div>
        </x-filament::section>
    @else
        {{-- Date range selector & refresh --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Date Range:</label>
                <select wire:model.live="dateRange" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="7">Last 7 days</option>
                    <option value="28">Last 28 days</option>
                    <option value="90">Last 3 months</option>
                </select>
                <span class="text-xs text-gray-400">Data has ~3 day delay from Google</span>
            </div>
            <x-filament::button
                size="sm"
                color="gray"
                wire:click="refreshData"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="refreshData">Refresh Data</span>
                <span wire:loading wire:target="refreshData">Refreshing...</span>
            </x-filament::button>
        </div>

        {{-- Summary cards --}}
        @php $summary = $this->getSummaryProperty(); @endphp

        @if(isset($summary['error']))
            <x-filament::section>
                <div class="text-center py-4">
                    <p class="font-semibold text-danger-500">API Error</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $summary['error'] }}</p>
                </div>
            </x-filament::section>
        @else
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <x-filament::section>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-primary-600">{{ number_format($summary['clicks'] ?? 0) }}</p>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Total Clicks</p>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-purple-600">{{ number_format($summary['impressions'] ?? 0) }}</p>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Total Impressions</p>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-green-600">{{ $summary['ctr'] ?? 0 }}%</p>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Average CTR</p>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-orange-600">{{ $summary['position'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mt-1">Avg Position</p>
                    </div>
                </x-filament::section>
            </div>

            {{-- Tabs --}}
            <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
                <nav class="flex gap-4">
                    <button wire:click="$set('activeTab', 'queries')"
                        class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'queries' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                        Top Queries
                    </button>
                    <button wire:click="$set('activeTab', 'pages')"
                        class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'pages' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                        Top Pages
                    </button>
                </nav>
            </div>

            {{-- Top Queries --}}
            @if($activeTab === 'queries')
                @php $queries = $this->getQueryDataProperty(); @endphp

                @if(isset($queries['error']))
                    <p class="text-danger-500 text-sm">{{ $queries['error'] }}</p>
                @elseif(empty($queries))
                    <p class="text-gray-500 text-sm text-center py-8">No query data available for this period.</p>
                @else
                    <x-filament::section>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-gray-400">#</th>
                                        <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-gray-400">Query</th>
                                        <th class="text-right py-3 px-2 font-medium text-gray-600 dark:text-gray-400">Clicks</th>
                                        <th class="text-right py-3 px-2 font-medium text-gray-600 dark:text-gray-400">Impressions</th>
                                        <th class="text-right py-3 px-2 font-medium text-gray-600 dark:text-gray-400">CTR</th>
                                        <th class="text-right py-3 px-2 font-medium text-gray-600 dark:text-gray-400">Position</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($queries as $index => $row)
                                        <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                            <td class="py-2.5 px-2 text-gray-400 text-xs">{{ $index + 1 }}</td>
                                            <td class="py-2.5 px-2 font-mono text-xs">{{ $row['keys'][0] ?? '' }}</td>
                                            <td class="py-2.5 px-2 text-right font-medium">{{ number_format($row['clicks']) }}</td>
                                            <td class="py-2.5 px-2 text-right">{{ number_format($row['impressions']) }}</td>
                                            <td class="py-2.5 px-2 text-right">{{ $row['ctr'] }}%</td>
                                            <td class="py-2.5 px-2 text-right">{{ $row['position'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-filament::section>
                @endif
            @endif

            {{-- Top Pages --}}
            @if($activeTab === 'pages')
                @php $pages = $this->getPageDataProperty(); @endphp

                @if(isset($pages['error']))
                    <p class="text-danger-500 text-sm">{{ $pages['error'] }}</p>
                @elseif(empty($pages))
                    <p class="text-gray-500 text-sm text-center py-8">No page data available for this period.</p>
                @else
                    <x-filament::section>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-gray-400">#</th>
                                        <th class="text-left py-3 px-2 font-medium text-gray-600 dark:text-gray-400">Page</th>
                                        <th class="text-right py-3 px-2 font-medium text-gray-600 dark:text-gray-400">Clicks</th>
                                        <th class="text-right py-3 px-2 font-medium text-gray-600 dark:text-gray-400">Impressions</th>
                                        <th class="text-right py-3 px-2 font-medium text-gray-600 dark:text-gray-400">CTR</th>
                                        <th class="text-right py-3 px-2 font-medium text-gray-600 dark:text-gray-400">Position</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pages as $index => $row)
                                        <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                            <td class="py-2.5 px-2 text-gray-400 text-xs">{{ $index + 1 }}</td>
                                            <td class="py-2.5 px-2 font-mono text-xs truncate max-w-xs" title="{{ $row['keys'][0] ?? '' }}">
                                                {{ str_replace(config('app.url'), '', $row['keys'][0] ?? '') ?: '/' }}
                                            </td>
                                            <td class="py-2.5 px-2 text-right font-medium">{{ number_format($row['clicks']) }}</td>
                                            <td class="py-2.5 px-2 text-right">{{ number_format($row['impressions']) }}</td>
                                            <td class="py-2.5 px-2 text-right">{{ $row['ctr'] }}%</td>
                                            <td class="py-2.5 px-2 text-right">{{ $row['position'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-filament::section>
                @endif
            @endif
        @endif
    @endif
</x-filament-panels::page>
