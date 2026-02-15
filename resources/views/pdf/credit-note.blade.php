<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Credit Note {{ $creditNote->credit_note_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #C9A96E; }
        .doc-title { font-size: 28px; font-weight: bold; color: #d32f2f; text-align: right; }
        .meta { text-align: right; font-size: 11px; color: #666; }
        .section-title { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #C9A96E; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; }
        .info-grid td { padding: 3px 0; }
        .info-label { color: #666; width: 140px; }
        .totals { width: 300px; float: right; margin-top: 15px; }
        .totals td { padding: 5px 8px; }
        .totals .label { color: #666; }
        .totals .value { text-align: right; font-weight: bold; }
        .totals .grand-total { font-size: 16px; color: #d32f2f; border-top: 2px solid #d32f2f; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 15px; }
        .clearfix::after { content: ""; display: table; clear: both; }
    </style>
</head>
<body>
    <table width="100%" style="margin-bottom: 30px; border-bottom: 2px solid #d32f2f; padding-bottom: 15px;">
        <tr>
            <td><div class="logo">{{ $storeName ?? 'AD Perfumes' }}</div></td>
            <td style="text-align: right;">
                <div class="doc-title">CREDIT NOTE</div>
                <div class="meta">
                    {{ $creditNote->credit_note_number }}<br>
                    Date: {{ $creditNote->created_at->format('M d, Y') }}
                </div>
            </td>
        </tr>
    </table>

    <table width="100%" style="margin-bottom: 20px;">
        <tr>
            <td width="50%" style="vertical-align: top;">
                <div class="section-title">Reference</div>
                <table class="info-grid">
                    <tr><td class="info-label">Order #:</td><td>{{ $creditNote->order->order_number ?? 'N/A' }}</td></tr>
                    <tr><td class="info-label">Original Invoice:</td><td>{{ $creditNote->invoice->invoice_number ?? 'N/A' }}</td></tr>
                    <tr><td class="info-label">Refund #:</td><td>{{ $creditNote->refund->refund_number ?? 'N/A' }}</td></tr>
                    <tr><td class="info-label">Type:</td><td>{{ ucfirst(str_replace('_', ' ', $creditNote->type)) }}</td></tr>
                </table>
            </td>
            <td width="50%" style="vertical-align: top;">
                <div class="section-title">Merchant</div>
                <table class="info-grid">
                    <tr><td class="info-label">Business:</td><td>{{ $creditNote->merchant->business_name ?? $storeName ?? 'AD Perfumes' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="clearfix">
        <table class="totals">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="value">AED {{ number_format($creditNote->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td class="label">VAT:</td>
                <td class="value">AED {{ number_format($creditNote->tax_amount, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td class="label">Credit Total:</td>
                <td class="value">AED {{ number_format($creditNote->total, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($creditNote->reason)
        <div style="margin-top: 80px;">
            <div class="section-title">Reason</div>
            <p>{{ $creditNote->reason }}</p>
        </div>
    @endif

    <div class="footer">
        <p>This credit note is issued against the referenced invoice. VAT implications apply as per UAE FTA regulations.</p>
        <p>{{ $storeName ?? 'AD Perfumes' }}</p>
    </div>
</body>
</html>
