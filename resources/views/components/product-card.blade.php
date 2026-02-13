@props(['product'])

<div class="group bg-white overflow-hidden transition-all duration-300 flex flex-col">
    <!-- Product Image -->
    <a href="{{ route('products.show', $product->slug) }}" class="block relative overflow-hidden bg-brand-light">
        <div class="flex items-center justify-center relative p-3">
            @if($product->image)
                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="w-full h-auto group-hover:scale-[1.03] transition-transform duration-500">
            @else
                <div class="aspect-[3/4] w-full flex items-center justify-center">
                    <svg class="w-16 h-16 text-brand-border" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            @endif

            <!-- Badges -->
            <div class="absolute top-3 left-3 flex flex-col gap-2">
                @if($product->is_new)
                    <span class="bg-brand-dark text-white text-[9px] font-bold px-3 py-1 uppercase tracking-editorial">New</span>
                @endif
                @if($product->on_sale && $product->original_price && $product->original_price > $product->price)
                    <span class="bg-brand-sale text-white text-[9px] font-bold px-3 py-1 uppercase tracking-editorial">-{{ round((($product->original_price - $product->price) / $product->original_price) * 100) }}%</span>
                @endif
            </div>
        </div>
    </a>

    <!-- Product Info -->
    <div class="px-1 pt-4 pb-2 flex-1 flex flex-col">
        <!-- Brand -->
        <a href="{{ route('products.byBrand', $product->brand->slug) }}" class="text-[10px] text-brand-muted uppercase tracking-editorial font-medium mb-1 hover:text-brand-primary transition-colors">{{ $product->brand->name }}</a>

        <!-- Product Name -->
        <h3 class="text-[13px] font-medium text-brand-text mb-2 line-clamp-2 min-h-[36px] leading-[18px]">
            <a href="{{ route('products.show', $product->slug) }}" class="hover:text-brand-primary transition-colors duration-300">
                {{ $product->name }}
            </a>
        </h3>

        <!-- Price -->
        <div class="flex items-baseline gap-2 mb-3">
            @if($product->on_sale && $product->original_price)
                <span class="text-[14px] font-semibold text-brand-dark tabular-nums">AED {{ number_format($product->price, 0) }}</span>
                <span class="text-[11px] text-brand-muted line-through tabular-nums">AED {{ number_format($product->original_price, 0) }}</span>
            @else
                <span class="text-[14px] font-semibold text-brand-dark tabular-nums">AED {{ number_format($product->price, 0) }}</span>
            @endif
        </div>

        <!-- Add to Bag Button -->
        <form action="{{ route('cart.add') }}" method="POST" class="mt-auto">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="quantity" value="1">
            <button type="submit" class="w-full bg-brand-dark text-white text-[10px] font-semibold py-3 uppercase tracking-luxury hover:bg-brand-primary transition-colors duration-300">
                Add to Bag
            </button>
        </form>
    </div>
</div>
