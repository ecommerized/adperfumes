@extends('layouts.app')

@section('title', $product->name . ' - ' . ($storeName ?? 'AD Perfumes'))

@section('content')
<!-- Breadcrumb -->
<div class="bg-white border-b border-brand-border">
    <div class="max-w-8xl mx-auto px-6 lg:px-10 py-4">
        <nav class="text-[11px]">
            <ol class="flex items-center gap-2 text-brand-muted">
                <li><a href="{{ route('home') }}" class="hover:text-brand-dark transition-colors uppercase tracking-luxury">Home</a></li>
                <li class="text-brand-border">/</li>
                <li><a href="{{ route('products.index') }}" class="hover:text-brand-dark transition-colors uppercase tracking-luxury">Products</a></li>
                <li class="text-brand-border">/</li>
                <li><a href="{{ route('products.byBrand', $product->brand->slug) }}" class="hover:text-brand-dark transition-colors uppercase tracking-luxury">{{ $product->brand->name }}</a></li>
                <li class="text-brand-border">/</li>
                <li class="text-brand-text">{{ Str::limit($product->name, 40) }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="bg-brand-ivory">
    <div class="max-w-8xl mx-auto px-6 lg:px-10 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20">
            <!-- Product Image -->
            <div class="sticky top-24 self-start">
                <div class="bg-brand-light flex items-center justify-center relative overflow-hidden p-6">
                    @if($product->image)
                        <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="w-full h-auto">
                    @else
                        <svg class="w-32 h-32 text-brand-border" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    @endif

                    @if($product->is_new)
                        <div class="absolute top-4 left-4">
                            <span class="bg-brand-dark text-white text-[9px] font-bold px-3 py-1 uppercase tracking-editorial">New</span>
                        </div>
                    @endif
                    @if($product->on_sale && $product->original_price && $product->original_price > $product->price)
                        <div class="absolute top-4 right-4">
                            <span class="bg-brand-sale text-white text-[9px] font-bold px-3 py-1 uppercase tracking-editorial">-{{ round((($product->original_price - $product->price) / $product->original_price) * 100) }}%</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Product Info -->
            <div class="space-y-8">
                <div>
                    <a href="{{ route('products.byBrand', $product->brand->slug) }}" class="text-[11px] text-brand-primary uppercase tracking-editorial hover:text-brand-primary-soft transition-colors font-semibold">
                        {{ $product->brand->name }}
                    </a>
                </div>

                <h1 class="font-display text-[32px] lg:text-[40px] font-bold text-brand-dark leading-tight">
                    {{ $product->name }}
                </h1>

                <!-- Price & Stock -->
                <div class="flex items-center gap-6 py-6 border-y border-brand-border">
                    <div>
                        @if($product->on_sale && $product->original_price)
                            <p class="text-[13px] text-brand-muted line-through mb-1 tabular-nums">AED {{ number_format($product->original_price, 0) }}</p>
                            <p class="text-[28px] font-bold text-brand-dark tabular-nums">AED {{ number_format($product->price, 0) }}</p>
                        @else
                            <p class="text-[28px] font-bold text-brand-dark tabular-nums">AED {{ number_format($product->price, 0) }}</p>
                        @endif
                    </div>

                    @if($product->stock > 0)
                        <div class="h-8 w-px bg-brand-border"></div>
                        <div class="text-[11px]">
                            <p class="font-bold text-brand-success uppercase tracking-luxury">In Stock</p>
                            <p class="text-brand-muted">{{ $product->stock }} available</p>
                        </div>
                    @else
                        <div class="h-8 w-px bg-brand-border"></div>
                        <div class="text-[11px]">
                            <p class="font-bold text-brand-sale uppercase tracking-luxury">Out of Stock</p>
                        </div>
                    @endif
                </div>

                <!-- BNPL Installment Widgets -->
                @include('partials.bnpl-widgets')

                <!-- Description -->
                @if($product->description)
                    <div>
                        <h2 class="text-[12px] font-bold uppercase tracking-luxury mb-3 text-brand-text">Description</h2>
                        <p class="text-[14px] text-brand-gray leading-relaxed">{{ $product->description }}</p>
                    </div>
                @endif

                <!-- Add to Cart -->
                <div class="bg-white border border-brand-border p-6">
                    <form action="{{ route('cart.add') }}" method="POST" class="space-y-5">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        <div>
                            <label for="quantity" class="block text-[11px] font-semibold text-brand-text uppercase tracking-luxury mb-2">Quantity</label>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" max="{{ $product->stock }}"
                                   class="w-24 border border-brand-border text-[14px] font-medium text-center py-2.5 focus:outline-none focus:border-brand-dark transition-colors tabular-nums">
                        </div>

                        @if($product->stock > 0)
                            <button type="submit" class="w-full bg-brand-dark text-white text-[11px] font-semibold uppercase tracking-luxury py-4 hover:bg-brand-primary transition-colors duration-300">
                                Add to Bag
                            </button>
                        @else
                            <button type="button" disabled class="w-full bg-brand-border text-brand-muted text-[11px] font-semibold uppercase tracking-luxury py-4 cursor-not-allowed">
                                Out of Stock
                            </button>
                        @endif

                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-brand-divider text-[10px]">
                            <div class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-brand-muted uppercase tracking-luxury">100% Authentic</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                                <span class="text-brand-muted uppercase tracking-luxury">Free Shipping</span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Fragrance Notes & Accords -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-16">
            <div class="bg-white border border-brand-border p-8">
                <h2 class="text-[14px] font-bold uppercase tracking-luxury mb-6 text-brand-dark">Fragrance Notes</h2>

                <div class="space-y-6">
                    @if($product->topNotes->count() > 0)
                        <div>
                            <h3 class="text-[11px] font-semibold uppercase tracking-editorial mb-3 text-brand-primary">Top Notes</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($product->topNotes as $note)
                                    <x-note-badge :note="$note" />
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($product->middleNotes->count() > 0)
                        <div>
                            <h3 class="text-[11px] font-semibold uppercase tracking-editorial mb-3 text-brand-primary">Middle Notes</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($product->middleNotes as $note)
                                    <x-note-badge :note="$note" />
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($product->baseNotes->count() > 0)
                        <div>
                            <h3 class="text-[11px] font-semibold uppercase tracking-editorial mb-3 text-brand-primary">Base Notes</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($product->baseNotes as $note)
                                    <x-note-badge :note="$note" />
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if($product->accords->count() > 0)
                <div class="bg-white border border-brand-border p-8">
                    <h2 class="text-[14px] font-bold uppercase tracking-luxury mb-6 text-brand-dark">Main Accords</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->accords as $accord)
                            <x-accord-badge :accord="$accord" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Related Products -->
        @if($relatedProducts->count() > 0)
            <div class="mt-20 pt-12 border-t border-brand-border">
                <div class="flex justify-between items-end mb-10">
                    <div>
                        <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-3">You May Also Like</p>
                        <h2 class="font-display text-[28px] lg:text-[36px] font-bold text-brand-dark leading-tight">
                            More from {{ $product->brand->name }}
                        </h2>
                    </div>
                    <x-button variant="outline" href="{{ route('products.byBrand', $product->brand->slug) }}" class="hidden lg:inline-flex">
                        View All
                    </x-button>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-5">
                    @foreach($relatedProducts as $related)
                        <x-product-card :product="$related" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
