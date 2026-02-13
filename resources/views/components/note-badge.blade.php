@props(['note'])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 bg-brand-light border border-brand-border px-3 py-1.5 text-[11px] text-brand-text']) }}>
    <svg class="w-3 h-3 text-brand-primary" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 3.5a1.5 1.5 0 013 0V4a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-.5a1.5 1.5 0 000 3h.5a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-.5a1.5 1.5 0 00-3 0v.5a1 1 0 01-1 1H6a1 1 0 01-1-1v-3a1 1 0 00-1-1h-.5a1.5 1.5 0 010-3H4a1 1 0 001-1V6a1 1 0 011-1h3a1 1 0 001-1v-.5z"/>
    </svg>
    {{ $note->name }}
</span>
