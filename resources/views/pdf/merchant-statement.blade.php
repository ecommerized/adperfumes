<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Merchant Statement</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { margin-bottom: 30px; border-bottom: 2px solid #C9A96E; padding-bottom: 15px; }
        .logo { font-size: 24px; font-weight: bold; color: #C9A96E; }
        .report-title { font-size: 22px; font-weight: bold; color: #333; margin-top: 5px; }
        .period { font-size: 11px; color: #666; margin-top: 5px; }
        .merchant-info { font-size: 12px; margin-top: 10px; }
        .merchant-info strong { color: #333; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #C9A96E; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { background: #f8f6f0; color: #333; font-size: 10px; text-transform: uppercase; padding: 6px 8px; text-align: left; border-bottom: 2px solid #C9A96E; }
        table.items td { padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 11px; }
        table.items td.right, table.items th.right { text-align: right; }
        table.summary { width: 300px; float: right; margin-top: 15px; }
        table.summary td { padding: 6px 8px; }
        table.summary td.label { color: #555; }
        table.summary td.value { text-align: right; font-weight: bold; }
        table.summary tr.total td { border-top: 2px solid #C9A96E; font-size: 14px; color: #C9A96E; }
        .clearfix::after { content: ""; display: table; clear: both; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 15px; clear: both; }
        .empty { color: #999; font-style: italic; padding: 10px; }
    </style>
</head>
<body>
    <table width="100%" style="margin-bottom: 25px; border-bottom: 2px solid #C9A96E; padding-bottom: 15px;">
        <tr>
            <td>
                <div class="logo">{{ $storeName ?? 'AD Perfumes' }}</div>
                <div class="report-title">Merchant Statement</div>
                <div class="period">Period: {{ $data['period_start']->format('M d, Y') }} — {{ $data['period_end']->format('M d, Y') }}</div>
            </td>
            <td style="text-align: right; vertical-align: top;">
                <div class="merchant-info">
                    <strong>{{ $data['merchant']->business_name }}</strong><br>
                    @if($data['merchant']->tax_registration)
                        TRN: {{ $data['merchant']->tax_registration }}<br>
                    @endif
                    {{ $data['merchant']->email ?? '' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Orders ({{ $data['order_count'] }})</div>
        @if($data['orders']->count() > 0)
        <table class="items">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="right">Amount (AED)</th>
                    <th class="right">Commission (AED)</th>
                    <th class="right">Net (AED)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['orders'] as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}</td>
                    <td>{{ ucfirst($order->status) }}</td>
                    <td class="right">{{ number_format($order->order_amount, 2) }}</td>
                    <td class="right">{{ number_format($order->commission, 2) }}</td>
                    <td class="right">{{ number_format($order->order_amount - $order->commission, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="empty">No orders in this period.</p>
        @endif
    </div>

    @if($data['refunds']->count() > 0)
    <div class="section">
        <div class="section-title">Refunds ({{ $data['refunds']->count() }})</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Refund #</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th class="right">Amount (AED)</th>
                    <th class="right">Commission Reversed (AED)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['refunds'] as $refund)
                <tr>
                    <td>{{ $refund->refund_number }}</td>
                    <td>{{ ucfirst($refund->type) }}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($refund->reason_category ?? '-')) }}</td>
                    <td class="right">{{ number_format($refund->refund_total, 2) }}</td>
                    <td class="right">{{ number_format($refund->total_commission_reversed, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($data['settlements']->count() > 0)
    <div class="section">
        <div class="section-title">Settlements Paid ({{ $data['settlements']->count() }})</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th class="right">Amount Paid (AED)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['settlements'] as $settlement)
                <tr>
                    <td>{{ $settlement->paid_at?->format('M d, Y') }}</td>
                    <td>{{ $settlement->transaction_reference ?? '-' }}</td>
                    <td class="right">{{ number_format($settlement->merchant_payout, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="clearfix">
        <table class="summary">
            <tr>
                <td class="label">Total Earned:</td>
                <td class="value">AED {{ number_format($data['total_gmv'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total Commission:</td>
                <td class="value">-AED {{ number_format($data['total_commission'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Net Earnings:</td>
                <td class="value">AED {{ number_format($data['net_earnings'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Refunds:</td>
                <td class="value">-AED {{ number_format($data['total_refunds'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Settlements Paid:</td>
                <td class="value">AED {{ number_format($data['total_settled'], 2) }}</td>
            </tr>
            <tr class="total">
                <td class="label">Outstanding Balance:</td>
                <td class="value">AED {{ number_format($data['outstanding_balance'], 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>This is a computer-generated merchant statement.</p>
        <p>{{ $storeName ?? 'AD Perfumes' }} | Generated {{ now()->format('M d, Y') }}</p>
    </div>
</body>
</html>
