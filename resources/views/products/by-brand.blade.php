@extends('layouts.app')

@section('title', $brand->name . ' Perfumes - ' . ($storeName ?? 'AD Perfumes'))

@section('content')
<!-- Brand Header -->
<div class="bg-brand-dark text-white">
    <div class="max-w-8xl mx-auto px-6 lg:px-10 py-14">
        <!-- Breadcrumb -->
        <nav class="text-[11px] mb-6">
            <ol class="flex items-center gap-2 text-brand-muted">
                <li><a href="{{ route('home') }}" class="hover:text-white transition-colors uppercase tracking-luxury">Home</a></li>
                <li class="text-brand-muted/50">/</li>
                <li><a href="{{ route('brands.index') }}" class="hover:text-white transition-colors uppercase tracking-luxury">Brands</a></li>
                <li class="text-brand-muted/50">/</li>
                <li class="text-brand-primary uppercase tracking-luxury">{{ $brand->name }}</li>
            </ol>
        </nav>

        <h1 class="font-display text-[40px] lg:text-[52px] font-bold leading-tight mb-3">
            {{ $brand->name }}
        </h1>
        @if($brand->description)
            <p class="text-[13px] text-brand-muted max-w-3xl mb-2">{{ $brand->description }}</p>
        @endif
        <p class="text-[13px] text-brand-muted">{{ $products->total() }} {{ Str::plural('product', $products->total()) }}</p>
    </div>
</div>

<!-- Products Grid -->
<div class="bg-brand-ivory">
    <div class="max-w-8xl mx-auto px-6 lg:px-10 py-12">
        <!-- Sort Bar -->
        <div class="flex justify-end items-center gap-3 mb-10">
            <span class="text-[11px] text-brand-muted uppercase tracking-luxury">Sort by:</span>
            <select class="border border-brand-border bg-white px-4 py-2.5 text-[11px] uppercase tracking-luxury font-medium focus:outline-none focus:border-brand-dark transition-colors">
                <option>Featured</option>
                <option>Price: Low to High</option>
                <option>Price: High to Low</option>
                <option>Name: A to Z</option>
                <option>Newest First</option>
            </select>
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
                    <p class="text-[13px] text-brand-gray mb-8">No products available for this brand yet</p>
                    <x-button variant="outline" href="{{ route('products.index') }}">
                        View All Products
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
