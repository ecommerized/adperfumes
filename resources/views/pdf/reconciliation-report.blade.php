<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reconciliation Report</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { margin-bottom: 30px; border-bottom: 2px solid #C9A96E; padding-bottom: 15px; }
        .logo { font-size: 24px; font-weight: bold; color: #C9A96E; }
        .report-title { font-size: 22px; font-weight: bold; color: #333; margin-top: 5px; }
        .period { font-size: 11px; color: #666; margin-top: 5px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 14px; font-weight: bold; text-transform: uppercase; color: #C9A96E; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; }
        .metrics-grid { width: 100%; margin-bottom: 20px; }
        .metrics-grid td { padding: 10px; width: 33%; vertical-align: top; }
        .metric-card { background: #f8f6f0; border: 1px solid #e8e0d0; border-radius: 4px; padding: 12px; text-align: center; }
        .metric-label { font-size: 10px; color: #666; text-transform: uppercase; margin-bottom: 5px; }
        .metric-value { font-size: 18px; font-weight: bold; color: #333; }
        table.reconciliation { width: 100%; border-collapse: collapse; }
        table.reconciliation td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; }
        table.reconciliation td.label { color: #555; width: 65%; }
        table.reconciliation td.value { text-align: right; font-weight: bold; width: 35%; }
        table.reconciliation tr.highlight td { background: #f8f6f0; border-top: 2px solid #C9A96E; font-size: 14px; }
        .discrepancy-ok { color: #22c55e; }
        .discrepancy-warn { color: #ef4444; }
        .notes { background: #fffbeb; border: 1px solid #fde68a; border-radius: 4px; padding: 12px; margin-top: 15px; }
        .notes-title { font-weight: bold; color: #92400e; margin-bottom: 5px; }
        .notes-content { color: #78350f; font-size: 11px; white-space: pre-line; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-reviewed { background: #dbeafe; color: #1e40af; }
        .status-approved { background: #dcfce7; color: #166534; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">{{ $storeName ?? 'AD Perfumes' }}</div>
        <div class="report-title">Reconciliation Report</div>
        <div class="period">{{ $reconciliation->reconciliation_number }} |
            <span class="status-badge status-{{ $reconciliation->status }}">{{ ucfirst($reconciliation->status) }}</span>
        </div>
        <div class="period">Period: {{ $reconciliation->period_start->format('M d, Y') }} — {{ $reconciliation->period_end->format('M d, Y') }}</div>
        <div class="period">Generated: {{ now()->format('M d, Y H:i') }}</div>
    </div>

    <table class="metrics-grid">
        <tr>
            <td>
                <div class="metric-card">
                    <div class="metric-label">Total Orders</div>
                    <div class="metric-value">{{ number_format($reconciliation->total_orders) }}</div>
                </div>
            </td>
            <td>
                <div class="metric-card">
                    <div class="metric-label">GMV</div>
                    <div class="metric-value">AED {{ number_format($reconciliation->total_gmv, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="metric-card">
                    <div class="metric-label">Commission Earned</div>
                    <div class="metric-value">AED {{ number_format($reconciliation->total_commission_earned, 2) }}</div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="metric-card">
                    <div class="metric-label">Tax Collected</div>
                    <div class="metric-value">AED {{ number_format($reconciliation->total_tax_collected, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="metric-card">
                    <div class="metric-label">Refunds Issued</div>
                    <div class="metric-value">AED {{ number_format($reconciliation->total_refunds_issued, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="metric-card">
                    <div class="metric-label">Settlements Paid</div>
                    <div class="metric-value">AED {{ number_format($reconciliation->total_settlements_paid, 2) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Financial Reconciliation</div>
        <table class="reconciliation">
            <tr>
                <td class="label">Gross Merchandise Value</td>
                <td class="value">AED {{ number_format($reconciliation->total_gmv, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Less: Commission Earned</td>
                <td class="value">-AED {{ number_format($reconciliation->total_commission_earned, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Less: Refunds Issued</td>
                <td class="value">-AED {{ number_format($reconciliation->total_refunds_issued, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Expected Merchant Payables</td>
                <td class="value">AED {{ number_format($reconciliation->total_gmv - $reconciliation->total_commission_earned - $reconciliation->total_refunds_issued, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Actual Settlements Paid</td>
                <td class="value">AED {{ number_format($reconciliation->total_settlements_paid, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Debit Notes Applied</td>
                <td class="value">AED {{ number_format($reconciliation->total_debit_notes, 2) }}</td>
            </tr>
            <tr class="highlight">
                <td class="label">Discrepancy</td>
                <td class="value {{ abs($reconciliation->discrepancy_amount) < 0.01 ? 'discrepancy-ok' : 'discrepancy-warn' }}">
                    AED {{ number_format($reconciliation->discrepancy_amount, 2) }}
                </td>
            </tr>
            <tr class="highlight">
                <td class="label">Net Platform Revenue</td>
                <td class="value">AED {{ number_format($reconciliation->net_platform_revenue, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($reconciliation->discrepancy_notes)
    <div class="notes">
        <div class="notes-title">Discrepancy Notes</div>
        <div class="notes-content">{{ $reconciliation->discrepancy_notes }}</div>
    </div>
    @endif

    <div class="footer">
        <p>This is a computer-generated reconciliation report.</p>
        @if($reconciliation->reviewed_by)
            <p>Reviewed by: {{ $reconciliation->reviewedBy?->name ?? 'N/A' }} on {{ $reconciliation->reviewed_at?->format('M d, Y') }}</p>
        @endif
        <p>{{ $storeName ?? 'AD Perfumes' }} | Generated {{ now()->format('M d, Y') }}</p>
    </div>
</body>
</html>
