{{-- BNPL Installment Widgets (Tabby & Tamara) --}}
@if(!empty($bnplWidgets['tabby']) || !empty($bnplWidgets['tamara']))
    <div class="space-y-3">
        {{-- Tabby Widget --}}
        @if(!empty($bnplWidgets['tabby']))
            <div class="bg-brand-light border border-brand-border p-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <span class="text-[10px] font-bold uppercase tracking-editorial text-brand-dark bg-white border border-brand-border px-2.5 py-1">tabby</span>
                    </div>
                    <div>
                        <p class="text-[13px] text-brand-text">
                            <span class="font-semibold">{{ $bnplWidgets['tabby']['installment_count'] }}</span> interest-free payments of
                            <span class="font-bold text-brand-dark tabular-nums">AED {{ number_format($bnplWidgets['tabby']['installment_amount'], 2) }}</span>
                        </p>
                        <p class="text-[10px] text-brand-muted uppercase tracking-luxury mt-0.5">No interest &middot; No fees</p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-brand-muted flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        @endif

        {{-- Tamara Widget --}}
        @if(!empty($bnplWidgets['tamara']))
            <div class="bg-brand-light border border-brand-border p-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <span class="text-[10px] font-bold uppercase tracking-editorial text-brand-dark bg-white border border-brand-border px-2.5 py-1">tamara</span>
                    </div>
                    <div>
                        <p class="text-[13px] text-brand-text">
                            <span class="font-semibold">{{ $bnplWidgets['tamara']['installment_count'] }}</span> monthly payments of
                            <span class="font-bold text-brand-dark tabular-nums">AED {{ number_format($bnplWidgets['tamara']['installment_amount'], 2) }}</span>
                        </p>
                        <p class="text-[10px] text-brand-muted uppercase tracking-luxury mt-0.5">Split your purchase &middot; 0% interest</p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-brand-muted flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        @endif
    </div>
@endif
