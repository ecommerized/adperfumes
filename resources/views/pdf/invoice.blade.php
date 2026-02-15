<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #C9A96E; padding-bottom: 15px; }
        .logo { font-size: 24px; font-weight: bold; color: #C9A96E; }
        .invoice-title { font-size: 28px; font-weight: bold; color: #333; text-align: right; }
        .invoice-meta { text-align: right; font-size: 11px; color: #666; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #C9A96E; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; }
        .info-grid { width: 100%; }
        .info-grid td { padding: 3px 0; vertical-align: top; }
        .info-label { color: #666; width: 140px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th { background: #f8f6f0; color: #333; font-size: 11px; text-transform: uppercase; padding: 8px; text-align: left; border-bottom: 2px solid #C9A96E; }
        table.items td { padding: 8px; border-bottom: 1px solid #eee; font-size: 11px; }
        table.items td.right, table.items th.right { text-align: right; }
        .totals { width: 300px; float: right; margin-top: 15px; }
        .totals td { padding: 5px 8px; }
        .totals .label { color: #666; }
        .totals .value { text-align: right; font-weight: bold; }
        .totals .grand-total { font-size: 16px; color: #C9A96E; border-top: 2px solid #C9A96E; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 15px; }
        .clearfix::after { content: ""; display: table; clear: both; }
    </style>
</head>
<body>
    <table width="100%" style="margin-bottom: 30px; border-bottom: 2px solid #C9A96E; padding-bottom: 15px;">
        <tr>
            <td><div class="logo">{{ $storeName ?? 'AD Perfumes' }}</div></td>
            <td style="text-align: right;">
                <div class="invoice-title">TAX INVOICE</div>
                <div class="invoice-meta">
                    {{ $invoice->invoice_number }}<br>
                    Date: {{ $invoice->created_at->format('M d, Y') }}
                </div>
            </td>
        </tr>
    </table>

    <table width="100%" style="margin-bottom: 20px;">
        <tr>
            <td width="50%" style="vertical-align: top;">
                <div class="section-title">Bill To</div>
                <table class="info-grid">
                    <tr><td class="info-label">Name:</td><td>{{ $invoice->customer_name }}</td></tr>
                    <tr><td class="info-label">Email:</td><td>{{ $invoice->customer_email }}</td></tr>
                    <tr><td class="info-label">Phone:</td><td>{{ $invoice->customer_phone }}</td></tr>
                    <tr><td class="info-label">Address:</td><td>{{ $invoice->customer_address }}</td></tr>
                </table>
            </td>
            <td width="50%" style="vertical-align: top;">
                <div class="section-title">Merchant</div>
                <table class="info-grid">
                    <tr><td class="info-label">Business:</td><td>{{ $invoice->merchant_name ?? $storeName ?? 'AD Perfumes' }}</td></tr>
                    @if($invoice->merchant_trn)
                        <tr><td class="info-label">TRN:</td><td>{{ $invoice->merchant_trn }}</td></tr>
                    @endif
                    <tr><td class="info-label">Order #:</td><td>{{ $invoice->order->order_number ?? 'N/A' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Items</div>
        <table class="items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>SKU</th>
                    <th class="right">Qty</th>
                    <th class="right">Unit Price</th>
                    <th class="right">VAT</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ $item->sku }}</td>
                        <td class="right">{{ $item->quantity }}</td>
                        <td class="right">AED {{ number_format($item->unit_price, 2) }}</td>
                        <td class="right">AED {{ number_format($item->tax_amount, 2) }}</td>
                        <td class="right">AED {{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="clearfix">
        <table class="totals">
            <tr>
                <td class="label">Subtotal (excl. VAT):</td>
                <td class="value">AED {{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td class="label">VAT ({{ $invoice->tax_rate ?? 5 }}%):</td>
                <td class="value">AED {{ number_format($invoice->tax_amount, 2) }}</td>
            </tr>
            @if($invoice->shipping_amount > 0)
                <tr>
                    <td class="label">Shipping:</td>
                    <td class="value">AED {{ number_format($invoice->shipping_amount, 2) }}</td>
                </tr>
            @endif
            @if($invoice->discount_amount > 0)
                <tr>
                    <td class="label">Discount:</td>
                    <td class="value">-AED {{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="grand-total">
                <td class="label">Grand Total:</td>
                <td class="value">AED {{ number_format($invoice->total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>This is a computer-generated tax invoice. VAT Registration applies as per UAE Federal Tax Authority regulations.</p>
        <p>{{ $storeName ?? 'AD Perfumes' }} | {{ $storeUrl ?? '' }}</p>
    </div>
</body>
</html>
