<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payout Report {{ $report->report_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #C9A96E; }
        .doc-title { font-size: 28px; font-weight: bold; color: #333; text-align: right; }
        .meta { text-align: right; font-size: 11px; color: #666; }
        .section-title { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #C9A96E; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; margin-top: 20px; }
        .info-grid td { padding: 3px 0; }
        .info-label { color: #666; width: 180px; }
        table.summary { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.summary th { background: #f8f6f0; color: #333; font-size: 11px; text-transform: uppercase; padding: 8px; text-align: left; border-bottom: 2px solid #C9A96E; }
        table.summary td { padding: 8px; border-bottom: 1px solid #eee; }
        table.summary td.right { text-align: right; }
        .highlight { background: #f8f6f0; padding: 15px; margin-top: 20px; }
        .highlight .amount { font-size: 24px; font-weight: bold; color: #2e7d32; }
        .highlight .label { font-size: 13px; color: #666; text-transform: uppercase; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 15px; }
    </style>
</head>
<body>
    <table width="100%" style="margin-bottom: 30px; border-bottom: 2px solid #C9A96E; padding-bottom: 15px;">
        <tr>
            <td><div class="logo">{{ $storeName ?? 'AD Perfumes' }}</div></td>
            <td style="text-align: right;">
                <div class="doc-title">PAYOUT REPORT</div>
                <div class="meta">
                    {{ $report->report_number }}<br>
                    Generated: {{ $report->created_at->format('M d, Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Merchant Details</div>
    <table class="info-grid">
        <tr><td class="info-label">Merchant:</td><td><strong>{{ $report->merchant->business_name ?? 'N/A' }}</strong></td></tr>
        <tr><td class="info-label">Payout Date:</td><td>{{ $report->payout_date->format('M d, Y') }}</td></tr>
        <tr><td class="info-label">Period:</td><td>{{ $report->period_start->format('M d, Y') }} — {{ $report->period_end->format('M d, Y') }}</td></tr>
        <tr><td class="info-label">Total Orders:</td><td>{{ $report->total_orders }}</td></tr>
    </table>

    <div class="section-title">Financial Summary</div>
    <table class="summary">
        <tr>
            <td>Gross Revenue (incl. VAT)</td>
            <td class="right">AED {{ number_format($report->gross_revenue, 2) }}</td>
        </tr>
        <tr>
            <td>VAT Collected</td>
            <td class="right">AED {{ number_format($report->total_tax_collected, 2) }}</td>
        </tr>
        <tr>
            <td>Platform Commission</td>
            <td class="right" style="color: #d32f2f;">- AED {{ number_format($report->total_commission, 2) }}</td>
        </tr>
        <tr>
            <td>Commission VAT</td>
            <td class="right" style="color: #d32f2f;">- AED {{ number_format($report->commission_tax, 2) }}</td>
        </tr>
    </table>

    <div class="highlight">
        <div class="label">Net Payout Amount</div>
        <div class="amount">AED {{ number_format($report->net_payout, 2) }}</div>
    </div>

    @if($report->settlement && $report->settlement->transaction_reference)
        <div class="section-title">Payment Details</div>
        <table class="info-grid">
            <tr><td class="info-label">Transaction Reference:</td><td>{{ $report->settlement->transaction_reference }}</td></tr>
            <tr><td class="info-label">Paid At:</td><td>{{ $report->settlement->paid_at?->format('M d, Y H:i') ?? 'Pending' }}</td></tr>
        </table>
    @endif

    <div class="footer">
        <p>This payout report summarizes the settlement for the specified period.</p>
        <p>{{ $storeName ?? 'AD Perfumes' }} Marketplace</p>
    </div>
</body>
</html>
