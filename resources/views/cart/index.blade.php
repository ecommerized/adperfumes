@extends('layouts.app')

@section('title', 'Shopping Cart - ' . ($storeName ?? 'AD Perfumes'))

@section('content')
<div class="bg-brand-ivory min-h-screen">
    <div class="max-w-8xl mx-auto px-6 lg:px-10 py-12">
        <h1 class="font-display text-[32px] lg:text-[40px] font-bold text-brand-dark mb-10">Shopping Bag</h1>

        @if(empty($cart) || count($cart) == 0)
            <div class="bg-white border border-brand-border p-16 text-center">
                <svg class="w-16 h-16 text-brand-border mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
                <h2 class="text-[18px] font-bold text-brand-dark mb-2">Your bag is empty</h2>
                <p class="text-[13px] text-brand-gray mb-8">Discover our curated collection of luxury fragrances.</p>
                <x-button variant="primary" href="{{ route('products.index') }}">
                    Continue Shopping
                </x-button>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <!-- Cart Items -->
                <div class="lg:col-span-2">
                    <div class="bg-white border border-brand-border">
                        @foreach($cart as $id => $item)
                            <div class="p-6 border-b border-brand-divider last:border-b-0">
                                <div class="flex items-start gap-6">
                                    <div class="w-20 h-20 bg-brand-light flex items-center justify-center flex-shrink-0">
                                        @if(!empty($item['image']))
                                            <img src="{{ Storage::url($item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                                        @else
                                            <svg class="w-8 h-8 text-brand-border" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] text-brand-muted uppercase tracking-editorial mb-1">{{ $item['brand'] }}</p>
                                        <h3 class="text-[14px] font-medium text-brand-dark mb-1">{{ $item['name'] }}</h3>
                                        <p class="text-[14px] font-semibold text-brand-dark tabular-nums">AED {{ number_format($item['price'], 2) }}</p>

                                        <div class="flex items-center mt-4 gap-4">
                                            <form action="{{ route('cart.update') }}" method="POST" class="flex items-center gap-2">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $id }}">
                                                <label for="quantity_{{ $id }}" class="text-[10px] text-brand-muted uppercase tracking-luxury">Qty:</label>
                                                <input type="number" id="quantity_{{ $id }}" name="quantity" value="{{ $item['quantity'] }}"
                                                       min="1" max="99"
                                                       class="w-16 border border-brand-border text-center text-[13px] py-1.5 focus:outline-none focus:border-brand-dark tabular-nums"
                                                       onchange="this.form.submit()">
                                            </form>

                                            <form action="{{ route('cart.remove', $id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-brand-muted hover:text-brand-sale text-[11px] font-medium uppercase tracking-luxury transition-colors">
                                                    Remove
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="text-right flex-shrink-0">
                                        <p class="text-[14px] font-semibold text-brand-dark tabular-nums">
                                            AED {{ number_format($item['price'] * $item['quantity'], 2) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex justify-between items-center">
                        <form action="{{ route('cart.clear') }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-brand-muted hover:text-brand-sale text-[11px] font-medium uppercase tracking-luxury transition-colors">
                                Clear Bag
                            </button>
                        </form>
                        <a href="{{ route('products.index') }}" class="text-brand-text hover:text-brand-primary text-[11px] font-medium uppercase tracking-luxury transition-colors">
                            Continue Shopping
                        </a>
                    </div>
                </div>

                <!-- Order Summary -->
                <div>
                    <div class="bg-white border border-brand-border p-6 sticky top-24">
                        <h2 class="text-[14px] font-bold uppercase tracking-luxury mb-6 text-brand-dark">Order Summary</h2>

                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-[13px]">
                                <span class="text-brand-gray">Subtotal</span>
                                <span class="text-brand-dark font-medium tabular-nums">AED {{ number_format($totals['subtotal'], 2) }}</span>
                            </div>
                            <div class="flex justify-between text-[13px]">
                                <span class="text-brand-gray">Shipping</span>
                                <span class="text-brand-dark font-medium">{{ $totals['shipping'] > 0 ? 'AED ' . number_format($totals['shipping'], 2) : 'Calculated at checkout' }}</span>
                            </div>
                            @if($totals['discount'] > 0)
                                <div class="flex justify-between text-[13px]">
                                    <span class="text-brand-gray">Discount</span>
                                    <span class="text-brand-success font-medium tabular-nums">-AED {{ number_format($totals['discount'], 2) }}</span>
                                </div>
                            @endif
                            <div class="border-t border-brand-border pt-3 flex justify-between">
                                <span class="text-[14px] font-bold uppercase tracking-luxury text-brand-dark">Total</span>
                                <span class="text-[18px] font-bold text-brand-dark tabular-nums">AED {{ number_format($totals['grand_total'], 2) }}</span>
                            </div>
                        </div>

                        <a href="{{ route('checkout.index') }}" class="block w-full bg-brand-dark text-white text-center text-[11px] font-semibold uppercase tracking-luxury py-4 hover:bg-brand-primary transition-colors duration-300">
                            Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
