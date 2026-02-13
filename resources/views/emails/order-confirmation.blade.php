<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px 20px;
        }
        .success-badge {
            background-color: #10b981;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .order-number {
            font-size: 18px;
            font-weight: bold;
            color: #d97706;
            margin: 10px 0;
        }
        .section {
            margin: 25px 0;
            padding: 20px;
            background-color: #f9fafb;
            border-radius: 6px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 15px;
            border-bottom: 2px solid #d97706;
            padding-bottom: 8px;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-weight: bold;
            color: #111827;
        }
        .item-brand {
            color: #6b7280;
            font-size: 14px;
        }
        .item-quantity {
            color: #6b7280;
            font-size: 14px;
        }
        .item-price {
            font-weight: bold;
            color: #d97706;
        }
        .totals {
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        .grand-total {
            font-size: 18px;
            font-weight: bold;
            border-top: 2px solid #d97706;
            padding-top: 12px;
            margin-top: 12px;
        }
        .button {
            display: inline-block;
            background-color: #d97706;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 12px;
            background-color: #f9fafb;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .info-item {
            font-size: 14px;
        }
        .info-label {
            color: #6b7280;
        }
        .info-value {
            font-weight: bold;
            color: #111827;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>AD Perfumes</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Luxury Fragrances</p>
        </div>

        <div class="content">
            <div style="text-align: center;">
                <div class="success-badge">✓ Order Confirmed</div>
                <h2 style="margin: 10px 0;">Thank You for Your Order!</h2>
                <p class="order-number">Order #{{ $order->order_number }}</p>
                <p style="color: #6b7280;">{{ $order->created_at->format('F d, Y \a\t h:i A') }}</p>
            </div>

            <!-- Order Details -->
            <div class="section">
                <div class="section-title">Order Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Payment Status</div>
                        <div class="info-value" style="color: {{ $order->payment_status === 'paid' ? '#10b981' : '#f59e0b' }}">
                            {{ ucfirst($order->payment_status) }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Order Status</div>
                        <div class="info-value">{{ ucfirst($order->status) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Method</div>
                        <div class="info-value">{{ ucfirst($order->payment_method ?? 'N/A') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Shipping Method</div>
                        <div class="info-value">{{ ucfirst($order->shipping_method) }}</div>
                    </div>
                </div>
            </div>

            <!-- Shipping Address -->
            <div class="section">
                <div class="section-title">Shipping Address</div>
                <div style="font-size: 14px;">
                    <strong>{{ $order->full_name }}</strong><br>
                    {{ $order->address }}<br>
                    {{ $order->city }}, {{ $order->country }}
                    @if($order->postal_code)
                        {{ $order->postal_code }}
                    @endif
                    <br><br>
                    <strong>Email:</strong> {{ $order->email }}<br>
                    <strong>Phone:</strong> {{ $order->phone }}
                </div>
            </div>

            <!-- Order Items -->
            <div class="section">
                <div class="section-title">Order Items</div>
                @foreach($order->items as $item)
                    <div class="order-item">
                        <div class="item-details">
                            <div class="item-brand">{{ $item->brand_name }}</div>
                            <div class="item-name">{{ $item->product_name }}</div>
                            <div class="item-quantity">Qty: {{ $item->quantity }} × AED {{ number_format($item->price, 2) }}</div>
                        </div>
                        <div class="item-price">
                            AED {{ number_format($item->subtotal, 2) }}
                        </div>
                    </div>
                @endforeach

                <!-- Order Totals -->
                <div class="totals">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>AED {{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    <div class="total-row">
                        <span>Shipping</span>
                        <span>AED {{ number_format($order->shipping, 2) }}</span>
                    </div>
                    @if($order->discount > 0)
                        <div class="total-row" style="color: #10b981;">
                            <span>Discount @if($order->discount_code)({{ $order->discount_code }})@endif</span>
                            <span>-AED {{ number_format($order->discount, 2) }}</span>
                        </div>
                    @endif
                    <div class="total-row grand-total">
                        <span>Grand Total</span>
                        <span>AED {{ number_format($order->grand_total, 2) }}</span>
                    </div>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="{{ route('order.confirmation', $order->order_number) }}" class="button">
                    View Order Details
                </a>
            </div>

            @if($order->payment_status === 'paid')
                <div style="background-color: #ecfdf5; border: 1px solid #10b981; border-radius: 6px; padding: 15px; margin-top: 20px; text-align: center;">
                    <p style="margin: 0; color: #065f46;">
                        <strong>Your order will be shipped soon!</strong><br>
                        <span style="font-size: 14px;">We'll send you a tracking number once your order ships.</span>
                    </p>
                </div>
            @endif
        </div>

        <div class="footer">
            <p><strong>AD Perfumes</strong></p>
            <p>Luxury Fragrances Delivered to Your Door</p>
            <p style="margin-top: 15px;">
                Need help? Contact us at <a href="mailto:support@adperfumes.com" style="color: #d97706;">support@adperfumes.com</a>
            </p>
            <p style="margin-top: 15px; color: #9ca3af;">
                &copy; {{ date('Y') }} AD Perfumes. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
