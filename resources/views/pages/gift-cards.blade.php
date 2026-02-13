@extends('layouts.app')

@section('title', 'Gift Cards - ' . ($storeName ?? 'AD Perfumes'))
@section('description', 'Give the gift of luxury fragrance. AD Perfumes gift cards available in multiple denominations.')

@section('content')
<!-- Page Header -->
<section class="bg-brand-dark text-white py-20">
    <div class="max-w-6xl mx-auto px-6 lg:px-10 text-center">
        <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-4">The Perfect Gift</p>
        <h1 class="font-display text-[48px] lg:text-[64px] font-bold mb-6 leading-tight">
            Gift Cards
        </h1>
        <p class="text-[15px] text-brand-muted max-w-3xl mx-auto leading-relaxed">
            Let them choose their own luxury scent from our collection of over 6,000 authentic perfumes.
        </p>
    </div>
</section>

<!-- Gift Card Options -->
<section class="bg-white py-20">
    <div class="max-w-6xl mx-auto px-6 lg:px-10">
        <div class="text-center mb-16">
            <h2 class="font-display text-[32px] lg:text-[40px] font-bold text-brand-dark mb-4 leading-tight">
                Choose a Value
            </h2>
            <p class="text-[13px] text-brand-gray max-w-2xl mx-auto">
                Select from our range of gift card values, perfect for any occasion
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 max-w-4xl mx-auto">
            @foreach([100, 250, 500, 1000] as $amount)
            <div class="group bg-brand-light hover:bg-brand-dark border border-brand-border hover:border-brand-dark p-8 text-center transition-all duration-300 cursor-pointer">
                <div class="text-[11px] text-brand-muted group-hover:text-brand-muted uppercase tracking-editorial mb-2 transition-colors">Gift Card</div>
                <div class="text-[32px] font-bold text-brand-dark group-hover:text-white mb-1 transition-colors tabular-nums">AED {{ number_format($amount) }}</div>
                <div class="text-[11px] text-brand-muted group-hover:text-brand-muted transition-colors uppercase tracking-luxury">E-Gift Card</div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-12">
            <p class="text-[13px] text-brand-gray mb-8">
                Gift cards will be available for purchase soon. Contact us for custom amounts.
            </p>
            <x-button variant="primary" href="{{ route('contact') }}">
                Contact Us
            </x-button>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="bg-brand-light py-20">
    <div class="max-w-6xl mx-auto px-6 lg:px-10">
        <div class="text-center mb-16">
            <h2 class="font-display text-[28px] font-bold text-brand-dark leading-tight">
                How It Works
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
            @foreach([
                ['num' => '1', 'title' => 'Choose Amount', 'desc' => 'Select from our preset values or request a custom amount.'],
                ['num' => '2', 'title' => 'Send as Gift', 'desc' => 'Receive a digital gift card via email to forward to the recipient.'],
                ['num' => '3', 'title' => 'Redeem & Enjoy', 'desc' => 'The recipient uses the code at checkout to shop any fragrance.'],
            ] as $step)
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-brand-dark mb-6">
                    <span class="text-[16px] font-bold text-brand-primary">{{ $step['num'] }}</span>
                </div>
                <h3 class="text-[13px] font-bold text-brand-dark mb-3 uppercase tracking-luxury">{{ $step['title'] }}</h3>
                <p class="text-[13px] text-brand-gray leading-relaxed">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
