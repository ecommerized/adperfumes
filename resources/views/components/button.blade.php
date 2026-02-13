@props([
    'variant' => 'primary',
    'size' => 'default',
    'type' => 'button',
    'href' => null,
])

@php
$baseClasses = 'inline-flex items-center justify-center font-semibold uppercase tracking-luxury transition-all duration-300';

$variantClasses = [
    'primary' => 'bg-brand-dark text-white hover:bg-brand-primary',
    'secondary' => 'bg-brand-primary text-white hover:bg-brand-primary-soft',
    'outline' => 'bg-transparent text-brand-dark border border-brand-dark hover:bg-brand-dark hover:text-white',
    'ghost' => 'bg-transparent text-brand-gray border border-brand-border hover:border-brand-dark hover:text-brand-dark',
];

$sizeClasses = [
    'sm' => 'text-[10px] px-5 py-2.5',
    'default' => 'text-[11px] px-8 py-3.5',
    'lg' => 'text-[12px] px-10 py-4',
];

$classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['primary']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['default']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
