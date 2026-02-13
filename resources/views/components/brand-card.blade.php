@props(['brand'])

<a href="{{ route('products.byBrand', $brand->slug) }}" class="group block bg-white border border-brand-border p-8 text-center hover:border-brand-dark transition-all duration-300">
    @if($brand->logo)
        <div class="h-14 flex items-center justify-center mb-4">
            <img src="{{ Storage::url($brand->logo) }}" alt="{{ $brand->name }}" class="max-h-full max-w-full object-contain grayscale group-hover:grayscale-0 transition-all duration-300">
        </div>
    @else
        <h3 class="text-[16px] font-bold text-brand-dark uppercase tracking-luxury group-hover:text-brand-primary transition-colors duration-300">
            {{ $brand->name }}
        </h3>
    @endif

    <p class="text-[11px] text-brand-muted mt-2">{{ $brand->products_count }} {{ Str::plural('product', $brand->products_count) }}</p>

    <div class="mt-4 inline-flex items-center text-[10px] text-brand-dark font-semibold uppercase tracking-luxury opacity-0 group-hover:opacity-100 transition-opacity duration-300">
        Explore
        <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </div>
</a>
