{{-- Official Tabby & Tamara BNPL Widgets --}}
@php
    $settingsService = app(\App\Services\SettingsService::class);
    $tabbyPublicKey = $settingsService->get('payment_tabby_public_key', '');
    $tabbyMerchantCode = $settingsService->get('payment_tabby_merchant_code', '');
    $tamaraPublicKey = $settingsService->get('payment_tamara_public_key', '');
    $storeCurrency = $settingsService->get('store_currency', 'AED');
    $storeCountry = $settingsService->get('store_country', 'AE');
@endphp

<div class="space-y-3">
    {{-- Tabby Official Widget --}}
    <div id="tabbyPromo"></div>

    {{-- Tamara Official Widget --}}
    <tamara-widget
        type="tamara-summary"
        amount="{{ $product->price }}"
        inline-type="3"
        country="{{ $storeCountry }}"
        lang="en"
        currency="{{ $storeCurrency }}"
        @if($tamaraPublicKey) public-key="{{ $tamaraPublicKey }}" @endif
    ></tamara-widget>
</div>

@push('scripts')
{{-- Tabby Promo Script --}}
@if($tabbyPublicKey)
<script src="https://checkout.tabby.ai/tabby-promo.js"></script>
<script>
    new TabbyPromo({
        selector: '#tabbyPromo',
        currency: '{{ $storeCurrency }}',
        price: {{ $product->price }},
        installmentsCount: 4,
        lang: 'en',
        source: 'product',
        publicKey: '{{ $tabbyPublicKey }}',
        merchantCode: '{{ $tabbyMerchantCode }}'
    });
</script>
@endif

{{-- Tamara Widget Script --}}
<script src="https://cdn.tamara.co/widget-v2/tamara-widget.js"></script>
@endpush
