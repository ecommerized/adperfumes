@extends('layouts.app')

@section('title', 'All Perfumes - ' . ($storeName ?? 'AD Perfumes'))

@section('content')
<!-- Page Header -->
<div class="bg-brand-dark text-white">
    <div class="max-w-8xl mx-auto px-6 lg:px-10 py-14">
        <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-3">Collection</p>
        <h1 class="font-display text-[40px] lg:text-[52px] font-bold leading-tight mb-3">
            Shop All
        </h1>
        <p class="text-[13px] text-brand-muted">
            {{ $products->total() }} luxury fragrances
            @if(request('q'))
                for "<span class="text-brand-primary">{{ request('q') }}</span>"
            @endif
        </p>
    </div>
</div>

<!-- Products Grid -->
<div class="bg-brand-ivory">
    <div class="max-w-8xl mx-auto px-6 lg:px-10 py-12">
        <!-- Sort Bar -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-10">
            <div class="flex items-center gap-4">
                <button class="lg:hidden text-[11px] font-semibold uppercase tracking-luxury text-brand-text hover:text-brand-primary transition-colors duration-300 flex items-center gap-2 border border-brand-border px-4 py-2.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filters
                </button>
            </div>

            <div class="flex items-center gap-3 w-full lg:w-auto">
                <span class="text-[11px] text-brand-muted uppercase tracking-luxury">Sort by:</span>
                <select onchange="window.location.href=this.value"
                        class="border border-brand-border bg-white px-4 py-2.5 text-[11px] uppercase tracking-luxury font-medium focus:outline-none focus:border-brand-dark transition-colors">
                    <option value="{{ request()->fullUrlWithQuery(['sort' => 'newest']) }}" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Newest First</option>
                    <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_asc']) }}" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_desc']) }}" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    <option value="{{ request()->fullUrlWithQuery(['sort' => 'name_asc']) }}" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Name: A to Z</option>
                </select>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-5">
            @forelse($products as $product)
                <x-product-card :product="$product" />
            @empty
                <div class="col-span-full text-center py-24">
                    <svg class="w-20 h-20 text-brand-border mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <h3 class="text-[16px] font-bold text-brand-dark mb-2 uppercase tracking-luxury">No Products Found</h3>
                    <p class="text-[13px] text-brand-gray mb-8">Try adjusting your filters or search criteria</p>
                    <x-button variant="outline" href="{{ route('products.index') }}">
                        Clear Filters
                    </x-button>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($products->hasPages())
            <div class="mt-14 flex justify-center">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
