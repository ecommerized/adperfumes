@extends('layouts.app')

@section('title', ($storeName ?? 'AD Perfumes') . ' - Luxury Fragrances in UAE')

@section('content')
<!-- Hero Section -->
<section class="relative bg-brand-dark overflow-hidden">
    <div class="max-w-8xl mx-auto px-6 lg:px-10 py-28 lg:py-40">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div class="max-w-xl relative z-10">
                <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-6">The Art of Fragrance</p>
                <h1 class="font-display text-[52px] lg:text-[72px] font-bold text-white mb-6 leading-[1.05] tracking-tight">
                    Discover<br>
                    Your Scent
                </h1>
                <p class="text-[15px] text-brand-muted mb-10 leading-relaxed max-w-md">
                    Explore authentic luxury fragrances from the world's most prestigious perfume houses.
                    Curated with care, delivered across UAE.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center bg-brand-primary text-white text-[11px] font-semibold uppercase tracking-luxury px-10 py-4 hover:bg-brand-primary-soft transition-colors duration-300">
                        Shop Collection
                    </a>
                    <a href="{{ route('brands.index') }}" class="inline-flex items-center justify-center bg-transparent text-white border border-white/30 text-[11px] font-semibold uppercase tracking-luxury px-10 py-4 hover:bg-white hover:text-brand-dark transition-all duration-300">
                        Explore Brands
                    </a>
                </div>
            </div>

            <div class="relative hidden lg:block">
                <div class="aspect-[4/5] bg-brand-charcoal flex items-center justify-center">
                    <svg class="w-32 h-32 text-white/5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Shop by Brand -->
@if($brands->count() > 0)
<section class="bg-white py-20 border-t border-brand-divider">
    <div class="max-w-8xl mx-auto px-6 lg:px-10">
        <div class="text-center mb-14">
            <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-3">Our Partners</p>
            <h2 class="font-display text-[36px] lg:text-[44px] font-bold text-brand-dark leading-tight">
                Shop by Brand
            </h2>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            @foreach($brands->take(10) as $brand)
                <x-brand-card :brand="$brand" />
            @endforeach
        </div>

        @if($brands->count() > 10)
            <div class="text-center mt-12">
                <x-button variant="outline" href="{{ route('brands.index') }}">
                    View All Brands
                </x-button>
            </div>
        @endif
    </div>
</section>
@endif

<!-- Featured Products -->
<section class="bg-brand-ivory py-20 border-t border-brand-divider">
    <div class="max-w-8xl mx-auto px-6 lg:px-10">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end mb-14">
            <div>
                <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-3">Curated Selection</p>
                <h2 class="font-display text-[36px] lg:text-[44px] font-bold text-brand-dark leading-tight">
                    Featured
                </h2>
            </div>
            <div class="mt-6 lg:mt-0">
                <x-button variant="outline" href="{{ route('products.index') }}">
                    View All
                    <svg class="w-3.5 h-3.5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </x-button>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-5">
            @foreach($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    </div>
</section>

<!-- Trust Indicators -->
<section class="bg-brand-dark text-white py-16">
    <div class="max-w-8xl mx-auto px-6 lg:px-10">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-10 h-10 mb-4">
                    <svg class="w-full h-full text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-[11px] font-semibold mb-1 uppercase tracking-luxury">100% Authentic</h3>
                <p class="text-[11px] text-brand-muted">Guaranteed genuine</p>
            </div>

            <div class="text-center">
                <div class="inline-flex items-center justify-center w-10 h-10 mb-4">
                    <svg class="w-full h-full text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </div>
                <h3 class="text-[11px] font-semibold mb-1 uppercase tracking-luxury">Free Shipping</h3>
                <p class="text-[11px] text-brand-muted">Orders over AED 300</p>
            </div>

            <div class="text-center">
                <div class="inline-flex items-center justify-center w-10 h-10 mb-4">
                    <svg class="w-full h-full text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-[11px] font-semibold mb-1 uppercase tracking-luxury">Fast Delivery</h3>
                <p class="text-[11px] text-brand-muted">2-3 business days</p>
            </div>

            <div class="text-center">
                <div class="inline-flex items-center justify-center w-10 h-10 mb-4">
                    <svg class="w-full h-full text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <h3 class="text-[11px] font-semibold mb-1 uppercase tracking-luxury">Secure Payment</h3>
                <p class="text-[11px] text-brand-muted">Multiple options</p>
            </div>
        </div>
    </div>
</section>
@endsection
