@props(['accord'])

<span {{ $attributes->merge(['class' => 'inline-block bg-brand-dark text-white px-3 py-1.5 text-[10px] font-semibold uppercase tracking-luxury']) }}>
    {{ $accord->name }}
</span>
