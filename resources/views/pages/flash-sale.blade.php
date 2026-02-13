@extends('layouts.app')

@section('title', 'Flash Sale - ' . ($storeName ?? 'AD Perfumes'))
@section('description', 'Limited-time deals on luxury fragrances. Exclusive savings on premium perfumes.')

@section('content')
<!-- Page Header -->
<section class="bg-brand-dark text-white py-20">
    <div class="max-w-6xl mx-auto px-6 lg:px-10 text-center">
        <div class="inline-flex items-center gap-2 bg-brand-sale/20 px-4 py-2 mb-6">
            <svg class="w-4 h-4 text-brand-primary" fill="currentColor" viewBox="0 0 24 24">
                <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="text-[10px] uppercase tracking-editorial font-semibold text-brand-primary">Limited Time Only</span>
        </div>
        <h1 class="font-display text-[48px] lg:text-[64px] font-bold mb-6 leading-tight">
            Flash Sale
        </h1>
        <p class="text-[15px] text-brand-muted max-w-3xl mx-auto leading-relaxed">
            Exclusive savings on authentic luxury fragrances.
            {{ $products->total() }} products on sale — while stocks last.
        </p>
    </div>
</section>

<!-- Products Grid -->
<section class="bg-brand-ivory py-12">
    <div class="max-w-8xl mx-auto px-6 lg:px-10">
        @if($products->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-5">
                @foreach($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-12">
                {{ $products->links() }}
            </div>
        @else
            <div class="text-center py-32">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-brand-light mb-8">
                    <svg class="w-10 h-10 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="font-display text-[32px] lg:text-[40px] font-bold text-brand-dark mb-6 leading-tight">
                    Next Sale Coming Soon
                </h2>
                <p class="text-[14px] text-brand-gray max-w-2xl mx-auto leading-relaxed mb-10">
                    We're preparing our next flash sale with exceptional deals on premium fragrances.
                    Subscribe to our newsletter to be the first to know.
                </p>
                <x-button variant="primary" href="{{ route('products.index') }}">
                    Browse All Products
                </x-button>
            </div>
        @endif
    </div>
</section>
@endsection
