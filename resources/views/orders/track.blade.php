@extends('layouts.app')

@section('title', 'Track Your Order - ' . ($storeName ?? 'AD Perfumes'))

@section('content')
<div class="bg-brand-ivory min-h-screen">
    <div class="max-w-4xl mx-auto px-6 lg:px-10 py-16">
        <!-- Page Header -->
        <div class="text-center mb-10">
            <h1 class="font-display text-[32px] font-bold text-brand-dark mb-2">Track Your Order</h1>
            <p class="text-[14px] text-brand-gray">Enter your order number or tracking number to check the status</p>
        </div>

        <!-- Search Form -->
        <div class="bg-white border border-brand-border p-8 mb-10">
            <form action="{{ route('order.track') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
                <input
                    type="text"
                    name="order"
                    value="{{ $orderNumber ?? '' }}"
                    placeholder="Enter order number (e.g. ADP-1001) or tracking number"
                    class="flex-1 px-4 py-3 border border-brand-border text-[14px] text-brand-dark placeholder-brand-muted focus:outline-none focus:border-brand-dark transition-colors"
                    required
                >
                <button
                    type="submit"
                    class="px-8 py-3 bg-brand-dark text-white text-[12px] uppercase tracking-luxury font-semibold hover:bg-brand-text transition-colors"
                >
                    Track Order
                </button>
            </form>
        </div>

        @if($orderNumber)
            @if($order)
                <!-- Order Found -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Order Status -->
                    <div class="bg-white border border-brand-border p-6">
                        <h2 class="text-[13px] font-bold uppercase tracking-luxury mb-5 text-brand-dark">Order Status</h2>
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
                                <span class="text-brand-gray">Status</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-luxury
                                    @switch($order->status)
                                        @case('pending') bg-yellow-50 text-yellow-700 @break
                                        @case('confirmed') bg-blue-50 text-blue-700 @break
                                        @case('processing') bg-blue-50 text-blue-700 @break
                                        @case('shipped') bg-indigo-50 text-indigo-700 @break
                                        @case('delivered') bg-green-50 text-green-700 @break
                                        @case('cancelled') bg-red-50 text-red-700 @break
                                        @default bg-gray-50 text-gray-700
                                    @endswitch
                                ">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-brand-gray">Payment</span>
                                <span class="font-medium {{ $order->payment_status === 'paid' ? 'text-brand-success' : 'text-brand-primary' }}">
                                    {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}
                                </span>
                            </div>
                            @if($order->tracking_number)
                                <div class="flex justify-between">
                                    <span class="text-brand-gray">Tracking Number</span>
                                    <span class="font-medium text-brand-dark">{{ $order->tracking_number }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Shipping Info -->
                    <div class="bg-white border border-brand-border p-6">
                        <h2 class="text-[13px] font-bold uppercase tracking-luxury mb-5 text-brand-dark">Shipping Address</h2>
                        <div class="text-[13px] text-brand-gray space-y-1">
                            <p class="font-medium text-brand-dark">{{ $order->full_name }}</p>
                            <p>{{ $order->address }}</p>
                            <p>{{ $order->city }}, {{ $order->country }}</p>
                            @if($order->postal_code)
                                <p>{{ $order->postal_code }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Order Progress -->
                <div class="bg-white border border-brand-border p-6 mb-8">
                    <h2 class="text-[13px] font-bold uppercase tracking-luxury mb-6 text-brand-dark">Order Progress</h2>
                    @php
                        $steps = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
                        $currentIndex = array_search($order->status, $steps);
                        if ($currentIndex === false) $currentIndex = -1;
                    @endphp
                    <div class="flex items-center justify-between relative">
                        <!-- Progress Line -->
                        <div class="absolute top-5 left-0 right-0 h-[2px] bg-brand-border"></div>
                        @if($currentIndex >= 0)
                            <div class="absolute top-5 left-0 h-[2px] bg-brand-dark transition-all duration-500" style="width: {{ ($currentIndex / (count($steps) - 1)) * 100 }}%"></div>
                        @endif

                        @foreach($steps as $index => $step)
                            <div class="flex flex-col items-center relative z-10">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-[11px] font-bold
                                    {{ $index <= $currentIndex ? 'bg-brand-dark text-white' : 'bg-white border-2 border-brand-border text-brand-muted' }}
                                ">
                                    @if($index < $currentIndex)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @elseif($index === $currentIndex)
                                        <div class="w-2 h-2 bg-white rounded-full"></div>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </div>
                                <span class="text-[10px] uppercase tracking-luxury mt-2 {{ $index <= $currentIndex ? 'text-brand-dark font-semibold' : 'text-brand-muted' }}">
                                    {{ ucfirst($step) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Aramex Tracking Events -->
                @if($tracking && $tracking['success'] && !empty($tracking['events']))
                    <div class="bg-white border border-brand-border p-6 mb-8">
                        <h2 class="text-[13px] font-bold uppercase tracking-luxury mb-6 text-brand-dark">Shipment Tracking</h2>
                        <div class="space-y-0">
                            @foreach($tracking['events'] as $index => $event)
                                <div class="flex gap-4 {{ !$loop->last ? 'pb-6' : '' }}">
                                    <!-- Timeline -->
                                    <div class="flex flex-col items-center">
                                        <div class="w-3 h-3 rounded-full {{ $index === 0 ? 'bg-brand-dark' : 'bg-brand-border' }}"></div>
                                        @if(!$loop->last)
                                            <div class="w-[1px] flex-1 bg-brand-border mt-1"></div>
                                        @endif
                                    </div>
                                    <!-- Event Details -->
                                    <div class="flex-1 {{ !$loop->last ? 'pb-2' : '' }}">
                                        <p class="text-[13px] font-medium text-brand-dark">
                                            {{ $event['UpdateDescription'] ?? 'Status Update' }}
                                        </p>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1">
                                            @if(!empty($event['UpdateLocation']))
                                                <span class="text-[11px] text-brand-muted">
                                                    <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                    {{ $event['UpdateLocation'] }}
                                                </span>
                                            @endif
                                            @if(!empty($event['UpdateDateTime']))
                                                <span class="text-[11px] text-brand-muted">
                                                    <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    @php
                                                        $dateStr = $event['UpdateDateTime'];
                                                        if (preg_match('/\/Date\((\d+)([+-]\d+)?\)\//', $dateStr, $m)) {
                                                            $dateStr = \Carbon\Carbon::createFromTimestampMs((int)$m[1])->format('M d, Y h:i A');
                                                        }
                                                    @endphp
                                                    {{ $dateStr }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif($order->tracking_number && (!$tracking || !$tracking['success']))
                    <div class="bg-white border border-brand-border p-6 mb-8 text-center">
                        <p class="text-[13px] text-brand-gray">Tracking information will be available soon. Your shipment is being prepared.</p>
                    </div>
                @elseif(!$order->tracking_number)
                    <div class="bg-white border border-brand-border p-6 mb-8 text-center">
                        <p class="text-[13px] text-brand-gray">Your order is being prepared. Tracking information will be available once your order is shipped.</p>
                    </div>
                @endif

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
                                    <h3 class="text-[14px] font-medium text-brand-dark">{{ $item->product_name }}</h3>
                                    <p class="text-[11px] text-brand-muted tabular-nums">Qty: {{ $item->quantity }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[14px] font-semibold text-brand-dark tabular-nums">AED {{ number_format($item->subtotal, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Totals -->
                    <div class="border-t border-brand-border mt-4 pt-4 space-y-2">
                        <div class="flex justify-between text-[13px]">
                            <span class="text-brand-gray">Subtotal</span>
                            <span class="text-brand-dark tabular-nums">AED {{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-[13px]">
                            <span class="text-brand-gray">Shipping</span>
                            <span class="text-brand-dark tabular-nums">AED {{ number_format($order->shipping, 2) }}</span>
                        </div>
                        @if($order->discount > 0)
                            <div class="flex justify-between text-[13px]">
                                <span class="text-brand-gray">Discount</span>
                                <span class="text-brand-success tabular-nums">-AED {{ number_format($order->discount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between pt-2 border-t border-brand-border">
                            <span class="text-[14px] font-bold text-brand-dark">Total</span>
                            <span class="text-[16px] font-bold text-brand-dark tabular-nums">AED {{ number_format($order->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <x-button variant="outline" href="{{ route('home') }}">
                        Continue Shopping
                    </x-button>
                    @if($order->tracking_number)
                        <x-button variant="primary" href="https://www.aramex.com/track/results?ShipmentNumber={{ $order->tracking_number }}" target="_blank">
                            Track on Aramex
                        </x-button>
                    @endif
                </div>
            @else
                <!-- Order Not Found -->
                <div class="bg-white border border-brand-border p-10 text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 bg-brand-light mb-6">
                        <svg class="w-8 h-8 text-brand-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="font-display text-[24px] font-bold text-brand-dark mb-2">Order Not Found</h2>
                    <p class="text-[14px] text-brand-gray mb-6">We couldn't find an order with the number "{{ $orderNumber }}". Please check and try again.</p>
                    <p class="text-[12px] text-brand-muted">
                        Need help? Contact us at <a href="mailto:support@adperfumes.com" class="text-brand-primary hover:underline">support@adperfumes.com</a>
                    </p>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
