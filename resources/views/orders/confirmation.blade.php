@extends('layouts.app')

@section('title', 'Order Confirmation - ' . ($storeName ?? 'AD Perfumes'))

@section('content')
<div class="bg-brand-ivory min-h-screen">
    <div class="max-w-4xl mx-auto px-6 lg:px-10 py-16">
        <!-- Success/Pending Message -->
        @if($order->payment_status === 'paid')
            <div class="bg-white border border-brand-border p-10 mb-10 text-center">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-brand-light mb-6">
                    <svg class="w-8 h-8 text-brand-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="font-display text-[32px] font-bold text-brand-dark mb-2">Thank You</h1>
                <p class="text-[14px] text-brand-gray mb-1">Your payment was successful</p>
                <p class="text-[12px] text-brand-muted uppercase tracking-luxury">Order #{{ $order->order_number }}</p>
            </div>
        @else
            <div class="bg-white border border-brand-border p-10 mb-10 text-center">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-brand-light mb-6">
                    <svg class="w-8 h-8 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h1 class="font-display text-[32px] font-bold text-brand-dark mb-2">Order Received</h1>
                <p class="text-[14px] text-brand-gray mb-1">Payment status: {{ ucfirst($order->payment_status) }}</p>
                <p class="text-[12px] text-brand-muted uppercase tracking-luxury">Order #{{ $order->order_number }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Order Details -->
            <div class="bg-white border border-brand-border p-6">
                <h2 class="text-[13px] font-bold uppercase tracking-luxury mb-5 text-brand-dark">Order Details</h2>
                <div class="space-y-3 text-[13px]">
                    <div class="flex justify-between">
                        <span class="text-brand-gray">Order Number</span>
                        <span class="font-medium text-brand-dark">{{ $order->order_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-brand-gray">Order Date</span>
                        <span class="font-medium text-brand-dark">{{ $order->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-brand-gray">Payment Status</span>
                        <span class="font-medium {{ $order->payment_status === 'paid' ? 'text-brand-success' : 'text-brand-primary' }}">
                            {{ ucfirst($order->payment_status) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-brand-gray">Order Status</span>
                        <span class="font-medium text-brand-dark">{{ ucfirst($order->status) }}</span>
                    </div>
                </div>
            </div>

            <!-- Shipping Address -->
            <div class="bg-white border border-brand-border p-6">
                <h2 class="text-[13px] font-bold uppercase tracking-luxury mb-5 text-brand-dark">Shipping Address</h2>
                <div class="text-[13px] text-brand-gray space-y-1">
                    <p class="font-medium text-brand-dark">{{ $order->full_name }}</p>
                    <p>{{ $order->address }}</p>
                    <p>{{ $order->city }}, {{ $order->country }}</p>
                    @if($order->postal_code)
                        <p>{{ $order->postal_code }}</p>
                    @endif
                    <p class="mt-3"><span class="text-brand-muted">Email:</span> {{ $order->email }}</p>
                    <p><span class="text-brand-muted">Phone:</span> {{ $order->phone }}</p>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-white border border-brand-border p-6 mb-8">
            <h2 class="text-[13px] font-bold uppercase tracking-luxury mb-5 text-brand-dark">Order Items</h2>
            <div class="space-y-4">
                @foreach($order->items as $item)
                    <div class="flex items-center border-b border-brand-divider pb-4 last:border-b-0 last:pb-0">
                        <div class="w-16 h-16 bg-brand-light flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-brand-border" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-[10px] text-brand-muted uppercase tracking-editorial">{{ $item->brand_name }}</p>
                            <h3 class="text-[14px] font-medium text-brand-dark">{{ $item->product_name }}</h3>
                            <p class="text-[11px] text-brand-muted tabular-nums">Qty: {{ $item->quantity }} x AED {{ number_format($item->price, 2) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[14px] font-semibold text-brand-dark tabular-nums">AED {{ number_format($item->subtotal, 2) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Order Summary -->
        <div class="bg-white border border-brand-border p-6 mb-10">
            <h2 class="text-[13px] font-bold uppercase tracking-luxury mb-5 text-brand-dark">Order Summary</h2>
            <div class="space-y-3">
                <div class="flex justify-between text-[13px]">
                    <span class="text-brand-gray">Subtotal</span>
                    <span class="text-brand-dark tabular-nums">AED {{ number_format($order->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-[13px]">
                    <span class="text-brand-gray">Shipping ({{ ucfirst($order->shipping_method) }})</span>
                    <span class="text-brand-dark tabular-nums">AED {{ number_format($order->shipping, 2) }}</span>
                </div>
                @if($order->discount > 0)
                    <div class="flex justify-between text-[13px]">
                        <span class="text-brand-gray">Discount @if($order->discount_code)({{ $order->discount_code }})@endif</span>
                        <span class="text-brand-success tabular-nums">-AED {{ number_format($order->discount, 2) }}</span>
                    </div>
                @endif
                <div class="border-t border-brand-border pt-3 flex justify-between">
                    <span class="text-[14px] font-bold text-brand-dark">Grand Total</span>
                    <span class="text-[18px] font-bold text-brand-dark tabular-nums">AED {{ number_format($order->grand_total, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <x-button variant="outline" href="{{ route('home') }}">
                Continue Shopping
            </x-button>
            <x-button variant="outline" href="{{ route('order.track', ['order' => $order->order_number]) }}">
                Track Order
            </x-button>
            @if($order->payment_status === 'paid')
                <x-button variant="primary" type="button" onclick="window.print()">
                    Print Order
                </x-button>
            @endif
        </div>

        @if($order->payment_status !== 'paid')
            <div class="mt-10 bg-white border border-brand-border p-6 text-center">
                <p class="text-[13px] font-medium text-brand-dark mb-1">Need Help?</p>
                <p class="text-[12px] text-brand-gray">
                    Contact us at <a href="mailto:support@adperfumes.com" class="text-brand-primary hover:underline">support@adperfumes.com</a>
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
