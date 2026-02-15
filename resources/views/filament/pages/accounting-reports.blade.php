<x-filament-panels::page>
    {{-- Tab Navigation --}}
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="flex gap-4">
            <button wire:click="$set('activeTab', 'pl')"
                class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'pl' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                Profit & Loss
            </button>
            <button wire:click="$set('activeTab', 'tax')"
                class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'tax' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                Tax Report
            </button>
            <button wire:click="$set('activeTab', 'merchant')"
                class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'merchant' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                Merchant Statement
            </button>
            <button wire:click="$set('activeTab', 'reconciliation')"
                class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'reconciliation' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                Reconciliation
            </button>
        </nav>
    </div>

    {{-- Date Range (shared across all tabs) --}}
    <div class="flex items-center gap-3 mb-6 flex-wrap">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">From:</label>
        <input type="date" wire:model="reportFrom"
            class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">To:</label>
        <input type="date" wire:model="reportTo"
            class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500">

        @if($activeTab === 'merchant')
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 ml-2">Merchant:</label>
            <select wire:model="selectedMerchantId"
                class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500">
                <option value="">Select Merchant</option>
                @foreach($this->getMerchantsProperty() as $merchant)
                    <option value="{{ $merchant->id }}">{{ $merchant->business_name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    {{-- ===================== PROFIT & LOSS TAB ===================== --}}
    @if($activeTab === 'pl')
        <div class="flex items-center gap-3 mb-4">
            <x-filament::button wire:click="generateProfitLoss" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generateProfitLoss">Generate P&L Report</span>
                <span wire:loading wire:target="generateProfitLoss">Generating...</span>
            </x-filament::button>

            @if($downloadPath)
                <x-filament::button color="success" wire:click="downloadReport">
                    Download PDF
                </x-filament::button>
            @endif
        </div>

        @if($plData)
            <x-filament::section>
                <x-slot name="heading">Profit & Loss — {{ \Carbon\Carbon::parse($plData['period_start'])->format('M d, Y') }} to {{ \Carbon\Carbon::parse($plData['period_end'])->format('M d, Y') }}</x-slot>

                <div class="space-y-4">
                    {{-- Revenue --}}
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-2">Revenue</h4>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Gross Merchandise Value (GMV)</span>
                                <span class="font-medium">AED {{ number_format($plData['gmv'], 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Settled Commission</span>
                                <span class="font-medium">AED {{ number_format($plData['revenue']['settled_commission'], 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-500 pl-4">+ Unsettled (Accrued)</span>
                                <span class="text-gray-500">AED {{ number_format($plData['revenue']['unsettled_commission'], 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-bold border-t border-gray-200 dark:border-gray-700 pt-2">
                                <span class="text-primary-600">Total Commission Earned</span>
                                <span class="text-primary-600">AED {{ number_format($plData['revenue']['total_commission_earned'], 2) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Deductions --}}
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-2">Deductions</h4>
                        <div class="bg-red-50 dark:bg-red-900/10 rounded-lg p-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Commission Reversals ({{ $plData['deductions']['refund_count'] }} refunds)</span>
                                <span class="font-medium text-red-600">-AED {{ number_format($plData['deductions']['commission_reversals'], 2) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Summary --}}
                    <div class="bg-primary-50 dark:bg-primary-900/10 rounded-lg p-4 border border-primary-200 dark:border-primary-800">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600 dark:text-gray-400">Gross Profit</span>
                            <span class="font-bold">AED {{ number_format($plData['gross_profit'], 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600 dark:text-gray-400">Settlements Paid</span>
                            <span>AED {{ number_format($plData['settlements_paid'], 2) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t border-primary-200 dark:border-primary-700 pt-2">
                            <span class="text-primary-600">Net Platform Revenue</span>
                            <span class="text-primary-600">AED {{ number_format($plData['net_profit'], 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>Margin</span>
                            <span>{{ $plData['margin_percentage'] }}%</span>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endif
    @endif

    {{-- ===================== TAX REPORT TAB ===================== --}}
    @if($activeTab === 'tax')
        <div class="flex items-center gap-3 mb-4">
            <x-filament::button wire:click="generateTaxReport" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generateTaxReport">Generate Tax Report</span>
                <span wire:loading wire:target="generateTaxReport">Generating...</span>
            </x-filament::button>

            @if($downloadPath)
                <x-filament::button color="success" wire:click="downloadReport">
                    Download PDF
                </x-filament::button>
            @endif
        </div>

        @if($taxData)
            <x-filament::section>
                <x-slot name="heading">UAE VAT Report — {{ $taxData['report_number'] }}</x-slot>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-xl font-bold">AED {{ number_format($taxData['total_sales_incl_tax'], 2) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Sales (Incl. VAT)</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-xl font-bold">AED {{ number_format($taxData['total_sales_excl_tax'], 2) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Sales (Excl. VAT)</p>
                    </div>
                    <div class="text-center p-4 bg-amber-50 dark:bg-amber-900/10 rounded-lg border border-amber-200 dark:border-amber-800">
                        <p class="text-xl font-bold text-amber-600">AED {{ number_format($taxData['total_output_vat'], 2) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Output VAT</p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Commission Earned</span>
                        <span class="font-medium">AED {{ number_format($taxData['total_commission_earned'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Commission VAT</span>
                        <span class="font-medium">AED {{ number_format($taxData['total_commission_vat'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold border-t border-gray-200 dark:border-gray-700 pt-2">
                        <span class="text-primary-600">Net VAT Payable</span>
                        <span class="text-primary-600">AED {{ number_format($taxData['net_vat_payable'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 pt-1">
                        <span>{{ $taxData['total_orders'] }} orders from {{ $taxData['total_merchants'] }} merchants</span>
                    </div>
                </div>
            </x-filament::section>
        @endif
    @endif

    {{-- ===================== MERCHANT STATEMENT TAB ===================== --}}
    @if($activeTab === 'merchant')
        <div class="flex items-center gap-3 mb-4">
            <x-filament::button wire:click="generateMerchantStatement" wire:loading.attr="disabled"
                :disabled="!$selectedMerchantId">
                <span wire:loading.remove wire:target="generateMerchantStatement">Generate Statement</span>
                <span wire:loading wire:target="generateMerchantStatement">Generating...</span>
            </x-filament::button>

            @if($downloadPath)
                <x-filament::button color="success" wire:click="downloadReport">
                    Download PDF
                </x-filament::button>
            @endif
        </div>

        @if(!$selectedMerchantId)
            <x-filament::section>
                <div class="text-center py-8">
                    <x-heroicon-o-building-storefront class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                    <p class="text-sm text-gray-500">Select a merchant above to generate their statement.</p>
                </div>
            </x-filament::section>
        @elseif($merchantData)
            <x-filament::section>
                <x-slot name="heading">Statement — {{ $merchantData['merchant_name'] }}</x-slot>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-lg font-bold">{{ $merchantData['order_count'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Orders</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-lg font-bold">AED {{ number_format($merchantData['total_gmv'], 2) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total GMV</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-lg font-bold text-primary-600">AED {{ number_format($merchantData['total_commission'], 2) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Commission</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-lg font-bold text-green-600">AED {{ number_format($merchantData['net_earnings'], 2) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Net Earnings</p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Refunds</span>
                        <span class="font-medium text-red-500">-AED {{ number_format($merchantData['total_refunds'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Settlements Paid</span>
                        <span class="font-medium text-green-600">AED {{ number_format($merchantData['total_settled'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold border-t border-gray-200 dark:border-gray-700 pt-2">
                        <span class="text-primary-600">Outstanding Balance</span>
                        <span class="text-primary-600">AED {{ number_format($merchantData['outstanding_balance'], 2) }}</span>
                    </div>
                </div>
            </x-filament::section>
        @endif
    @endif

    {{-- ===================== RECONCILIATION TAB ===================== --}}
    @if($activeTab === 'reconciliation')
        <div class="flex items-center gap-3 mb-4">
            <x-filament::button wire:click="generateReconciliation" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generateReconciliation">Generate Reconciliation</span>
                <span wire:loading wire:target="generateReconciliation">Generating...</span>
            </x-filament::button>

            @if($downloadPath)
                <x-filament::button color="success" wire:click="downloadReport">
                    Download PDF
                </x-filament::button>
            @endif
        </div>

        @if($reconciliationData)
            <x-filament::section>
                <x-slot name="heading">Reconciliation — {{ $reconciliationData['reconciliation_number'] }}</x-slot>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-lg font-bold">{{ number_format($reconciliationData['total_orders']) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total Orders</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-lg font-bold">AED {{ number_format($reconciliationData['total_gmv'], 2) }}</p>
                        <p class="text-xs text-gray-500 mt-1">GMV</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-lg font-bold text-primary-600">AED {{ number_format($reconciliationData['total_commission_earned'], 2) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Commission</p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Tax Collected</span>
                        <span class="font-medium">AED {{ number_format($reconciliationData['total_tax_collected'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Refunds Issued</span>
                        <span class="font-medium text-red-500">AED {{ number_format($reconciliationData['total_refunds_issued'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Settlements Paid</span>
                        <span class="font-medium">AED {{ number_format($reconciliationData['total_settlements_paid'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Debit Notes</span>
                        <span class="font-medium">AED {{ number_format($reconciliationData['total_debit_notes'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold border-t border-gray-200 dark:border-gray-700 pt-2">
                        <span>Net Platform Revenue</span>
                        <span class="text-primary-600">AED {{ number_format($reconciliationData['net_platform_revenue'], 2) }}</span>
                    </div>
                </div>

                {{-- Discrepancy --}}
                <div class="mt-4 p-4 rounded-lg {{ abs($reconciliationData['discrepancy_amount']) < 0.01 ? 'bg-green-50 dark:bg-green-900/10 border border-green-200' : 'bg-red-50 dark:bg-red-900/10 border border-red-200' }}">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-bold {{ abs($reconciliationData['discrepancy_amount']) < 0.01 ? 'text-green-700' : 'text-red-700' }}">
                                Discrepancy: AED {{ number_format($reconciliationData['discrepancy_amount'], 2) }}
                            </p>
                            @if(abs($reconciliationData['discrepancy_amount']) < 0.01)
                                <p class="text-xs text-green-600 mt-1">All accounts reconciled successfully.</p>
                            @endif
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $reconciliationData['status'] === 'approved' ? 'bg-green-100 text-green-800' : ($reconciliationData['status'] === 'reviewed' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                            {{ ucfirst($reconciliationData['status']) }}
                        </span>
                    </div>

                    @if($reconciliationData['discrepancy_notes'])
                        <div class="mt-3 text-xs text-gray-600 dark:text-gray-400 whitespace-pre-line">
                            {{ $reconciliationData['discrepancy_notes'] }}
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
