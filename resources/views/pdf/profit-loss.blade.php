<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit & Loss Statement</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { margin-bottom: 30px; border-bottom: 2px solid #C9A96E; padding-bottom: 15px; }
        .logo { font-size: 24px; font-weight: bold; color: #C9A96E; }
        .report-title { font-size: 22px; font-weight: bold; color: #333; margin-top: 5px; }
        .period { font-size: 11px; color: #666; margin-top: 5px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 14px; font-weight: bold; text-transform: uppercase; color: #C9A96E; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; }
        table.financials { width: 100%; border-collapse: collapse; }
        table.financials td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; }
        table.financials td.label { color: #555; width: 65%; }
        table.financials td.value { text-align: right; font-weight: bold; width: 35%; }
        table.financials tr.subtotal td { border-top: 2px solid #C9A96E; font-size: 14px; color: #C9A96E; }
        table.financials tr.total td { border-top: 3px double #C9A96E; font-size: 16px; color: #333; background: #f8f6f0; }
        table.financials tr.indent td.label { padding-left: 30px; font-size: 11px; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 15px; }
        .summary-box { background: #f8f6f0; border: 1px solid #e8e0d0; border-radius: 4px; padding: 15px; margin-top: 20px; }
        .summary-box .metric { display: inline-block; width: 48%; margin-bottom: 10px; }
        .summary-box .metric-label { font-size: 10px; color: #666; text-transform: uppercase; }
        .summary-box .metric-value { font-size: 16px; font-weight: bold; color: #333; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">{{ $storeName ?? 'AD Perfumes' }}</div>
        <div class="report-title">Profit & Loss Statement</div>
        <div class="period">Period: {{ \Carbon\Carbon::parse($data['period_start'])->format('M d, Y') }} — {{ \Carbon\Carbon::parse($data['period_end'])->format('M d, Y') }}</div>
        <div class="period">Generated: {{ now()->format('M d, Y H:i') }}</div>
    </div>

    <div class="section">
        <div class="section-title">Revenue</div>
        <table class="financials">
            <tr>
                <td class="label">Gross Merchandise Value (GMV)</td>
                <td class="value">AED {{ number_format($data['gmv'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Settled Commission Revenue</td>
                <td class="value">AED {{ number_format($data['revenue']['settled_commission'], 2) }}</td>
            </tr>
            <tr class="indent">
                <td class="label">Unsettled Commission (Accrued)</td>
                <td class="value">AED {{ number_format($data['revenue']['unsettled_commission'], 2) }}</td>
            </tr>
            <tr class="subtotal">
                <td class="label">Total Commission Earned</td>
                <td class="value">AED {{ number_format($data['revenue']['total_commission_earned'], 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Deductions</div>
        <table class="financials">
            <tr>
                <td class="label">Commission Reversals ({{ $data['deductions']['refund_count'] }} refunds)</td>
                <td class="value">-AED {{ number_format($data['deductions']['commission_reversals'], 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Profit Summary</div>
        <table class="financials">
            <tr class="subtotal">
                <td class="label">Gross Profit</td>
                <td class="value">AED {{ number_format($data['gross_profit'], 2) }}</td>
            </tr>
            <tr>
                <td class="label">Settlements Paid to Merchants</td>
                <td class="value">AED {{ number_format($data['settlements_paid'], 2) }}</td>
            </tr>
            <tr class="total">
                <td class="label">Net Platform Revenue</td>
                <td class="value">AED {{ number_format($data['net_profit'], 2) }}</td>
            </tr>
        </table>
    </div>

    <table width="100%" style="margin-top: 20px;">
        <tr>
            <td width="50%" style="vertical-align: top;">
                <div class="summary-box">
                    <div class="metric">
                        <div class="metric-label">Profit Margin</div>
                        <div class="metric-value">{{ $data['margin_percentage'] }}%</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Total Orders (GMV)</div>
                        <div class="metric-value">AED {{ number_format($data['gmv'], 2) }}</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>This is a computer-generated financial report.</p>
        <p>{{ $storeName ?? 'AD Perfumes' }} | Generated {{ now()->format('M d, Y') }}</p>
    </div>
</body>
</html>
