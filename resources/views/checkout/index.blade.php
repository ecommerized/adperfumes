@extends('layouts.app')

@section('title', 'Checkout - ' . ($storeName ?? 'AD Perfumes'))

@section('content')
<div class="bg-brand-ivory min-h-screen">
    <!-- Header -->
    <div class="bg-white border-b border-brand-border">
        <div class="max-w-8xl mx-auto px-6 lg:px-10 py-6">
            <h1 class="font-display text-[32px] font-bold text-brand-dark">Checkout</h1>
        </div>
    </div>

    <div class="max-w-8xl mx-auto px-6 lg:px-10 py-12">
        <form action="{{ route('checkout.process') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- Checkout Form -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Contact Information -->
                    <div>
                        <h2 class="text-[13px] font-bold uppercase tracking-luxury mb-4 text-brand-dark">1. Contact Information</h2>
                        <div class="bg-white border border-brand-border p-6 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="email" class="block text-[11px] font-semibold text-brand-text uppercase tracking-luxury mb-2">Email *</label>
                                    <input type="email" name="email" id="email" required
                                           class="w-full border border-brand-border px-4 py-3 text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                                           value="{{ old('email') }}">
                                    @error('email')
                                        <p class="text-brand-sale text-[11px] mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="phone" class="block text-[11px] font-semibold text-brand-text uppercase tracking-luxury mb-2">Phone *</label>
                                    <input type="tel" name="phone" id="phone" required
                                           class="w-full border border-brand-border px-4 py-3 text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                                           value="{{ old('phone') }}">
                                    @error('phone')
                                        <p class="text-brand-sale text-[11px] mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div>
                        <h2 class="text-[13px] font-bold uppercase tracking-luxury mb-4 text-brand-dark">2. Shipping Address</h2>
                        <div class="bg-white border border-brand-border p-6 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-[11px] font-semibold text-brand-text uppercase tracking-luxury mb-2">First Name *</label>
                                    <input type="text" name="first_name" id="first_name" required
                                           class="w-full border border-brand-border px-4 py-3 text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                                           value="{{ old('first_name') }}">
                                    @error('first_name')
                                        <p class="text-brand-sale text-[11px] mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="last_name" class="block text-[11px] font-semibold text-brand-text uppercase tracking-luxury mb-2">Last Name *</label>
                                    <input type="text" name="last_name" id="last_name" required
                                           class="w-full border border-brand-border px-4 py-3 text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                                           value="{{ old('last_name') }}">
                                    @error('last_name')
                                        <p class="text-brand-sale text-[11px] mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="address" class="block text-[11px] font-semibold text-brand-text uppercase tracking-luxury mb-2">Address *</label>
                                <input type="text" name="address" id="address" required
                                       class="w-full border border-brand-border px-4 py-3 text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                                       value="{{ old('address') }}">
                                @error('address')
                                    <p class="text-brand-sale text-[11px] mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="city" class="block text-[11px] font-semibold text-brand-text uppercase tracking-luxury mb-2">City *</label>
                                    <input type="text" name="city" id="city" required
                                           class="w-full border border-brand-border px-4 py-3 text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                                           value="{{ old('city') }}">
                                    @error('city')
                                        <p class="text-brand-sale text-[11px] mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="country" class="block text-[11px] font-semibold text-brand-text uppercase tracking-luxury mb-2">Country *</label>
                                    <select name="country" id="country" required
                                            class="w-full border border-brand-border px-4 py-3 text-[13px] focus:outline-none focus:border-brand-dark transition-colors bg-white">
                                        <option value="AE" {{ old('country', 'AE') === 'AE' ? 'selected' : '' }}>United Arab Emirates</option>
                                        <option value="SA" {{ old('country') === 'SA' ? 'selected' : '' }}>Saudi Arabia</option>
                                        <option value="KW" {{ old('country') === 'KW' ? 'selected' : '' }}>Kuwait</option>
                                        <option value="QA" {{ old('country') === 'QA' ? 'selected' : '' }}>Qatar</option>
                                        <option value="BH" {{ old('country') === 'BH' ? 'selected' : '' }}>Bahrain</option>
                                        <option value="OM" {{ old('country') === 'OM' ? 'selected' : '' }}>Oman</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="postal_code" class="block text-[11px] font-semibold text-brand-text uppercase tracking-luxury mb-2">Postal Code</label>
                                    <input type="text" name="postal_code" id="postal_code"
                                           class="w-full border border-brand-border px-4 py-3 text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                                           value="{{ old('postal_code') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Method -->
                    <div>
                        <h2 class="text-[13px] font-bold uppercase tracking-luxury mb-4 text-brand-dark">3. Shipping Method</h2>
                        <div class="bg-white border border-brand-border p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[13px] font-semibold text-brand-dark">Standard Shipping (Aramex)</p>
                                    <p class="text-[11px] text-brand-muted mt-1">{{ $shippingRate['delivery_time'] }}</p>
                                </div>
                                <p class="text-[13px] font-semibold text-brand-dark tabular-nums">AED {{ number_format($shippingRate['rate'], 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Discount Code -->
                    <div>
                        <h2 class="text-[13px] font-bold uppercase tracking-luxury mb-4 text-brand-dark">4. Discount Code</h2>
                        <div class="bg-white border border-brand-border p-6">
                            <div class="flex gap-3">
                                <input type="text" name="discount_code" id="discount_code" placeholder="ENTER CODE"
                                       class="flex-1 border border-brand-border px-4 py-3 text-[12px] uppercase focus:outline-none focus:border-brand-dark transition-colors"
                                       value="{{ old('discount_code') }}">
                                <button type="button" id="apply-discount-btn" class="bg-brand-dark text-white px-6 py-3 text-[11px] font-semibold uppercase tracking-luxury hover:bg-brand-primary transition-colors duration-300">
                                    Apply
                                </button>
                            </div>
                            <div id="discount-message" class="mt-2 hidden"></div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <h2 class="text-[13px] font-bold uppercase tracking-luxury mb-4 text-brand-dark">5. Payment Method</h2>
                        <div class="bg-white border border-brand-border p-6 space-y-3">
                            @forelse($enabledPaymentMethods as $key => $method)
                                <label class="flex items-center justify-between p-4 border-2 cursor-pointer transition-colors payment-method-label
                                    {{ $loop->first ? 'border-brand-dark bg-brand-light' : 'border-brand-border hover:border-brand-dark' }}">
                                    <div class="flex items-center">
                                        <input type="radio" name="payment_method" value="{{ $key }}"
                                               {{ $loop->first ? 'checked' : '' }}
                                               class="w-4 h-4 text-brand-dark focus:ring-brand-dark">
                                        <div class="ml-3">
                                            <p class="text-[13px] font-semibold text-brand-dark">{{ $method['label'] }}</p>
                                            <p class="text-[11px] text-brand-muted">{{ $method['description'] }}</p>
                                        </div>
                                    </div>
                                    @if($loop->first)
                                        <span class="text-[9px] font-bold text-white bg-brand-dark px-3 py-1 uppercase tracking-editorial payment-selected-badge">Selected</span>
                                    @endif
                                </label>
                            @empty
                                <p class="text-[13px] text-brand-muted text-center py-4">No payment methods are currently available. Please contact support.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Order Summary Sidebar -->
                <div>
                    <div class="bg-white border border-brand-border p-6 sticky top-24">
                        <h2 class="text-[14px] font-bold uppercase tracking-luxury mb-6 text-brand-dark">Order Summary</h2>

                        <div class="space-y-4 mb-6 max-h-64 overflow-y-auto">
                            @foreach($cart as $item)
                                <div class="flex gap-3">
                                    <div class="w-14 h-14 bg-brand-light flex-shrink-0 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-brand-border" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[12px] font-medium text-brand-dark truncate">{{ $item['name'] }}</p>
                                        <p class="text-[10px] text-brand-muted uppercase tracking-luxury">Qty: {{ $item['quantity'] }}</p>
                                    </div>
                                    <p class="text-[12px] font-semibold text-brand-dark whitespace-nowrap tabular-nums">
                                        AED {{ number_format($item['price'] * $item['quantity'], 0) }}
                                    </p>
                                </div>
                            @endforeach
                        </div>

                        <div class="space-y-3 border-t border-brand-border pt-4">
                            <div class="flex justify-between text-[12px]">
                                <span class="text-brand-gray uppercase tracking-luxury">Subtotal</span>
                                <span class="text-brand-dark font-medium tabular-nums">AED {{ number_format($totals['subtotal'], 0) }}</span>
                            </div>
                            <div class="flex justify-between text-[12px]">
                                <span class="text-brand-gray uppercase tracking-luxury">Shipping</span>
                                <span class="text-brand-dark font-medium tabular-nums">AED {{ number_format($totals['shipping'], 0) }}</span>
                            </div>
                            @if($totals['discount'] > 0)
                                <div class="flex justify-between text-[12px]">
                                    <span class="text-brand-gray uppercase tracking-luxury">Discount</span>
                                    <span class="text-brand-success font-medium tabular-nums">-AED {{ number_format($totals['discount'], 0) }}</span>
                                </div>
                            @endif
                            <div class="border-t border-brand-border pt-3 flex justify-between">
                                <span class="text-[13px] font-bold uppercase tracking-luxury text-brand-dark">Total</span>
                                <span class="text-[18px] font-bold text-brand-dark tabular-nums">AED {{ number_format($totals['grand_total'], 0) }}</span>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-brand-dark text-white text-[11px] font-semibold uppercase tracking-luxury py-4 mt-6 hover:bg-brand-primary transition-colors duration-300">
                            Continue to Payment
                        </button>

                        <p class="text-[10px] text-brand-muted text-center mt-4 uppercase tracking-luxury">
                            Secure payment powered by Tap Payments
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.payment-method-label').forEach(label => {
            label.classList.remove('border-brand-dark', 'bg-brand-light');
            label.classList.add('border-brand-border');
            const badge = label.querySelector('.payment-selected-badge');
            if (badge) badge.remove();
        });
        const selected = this.closest('.payment-method-label');
        selected.classList.remove('border-brand-border');
        selected.classList.add('border-brand-dark', 'bg-brand-light');
        const badge = document.createElement('span');
        badge.className = 'text-[9px] font-bold text-white bg-brand-dark px-3 py-1 uppercase tracking-editorial payment-selected-badge';
        badge.textContent = 'Selected';
        selected.appendChild(badge);
    });
});
</script>
@endpush
