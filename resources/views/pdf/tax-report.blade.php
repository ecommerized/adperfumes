<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>UAE VAT Report</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { margin-bottom: 30px; border-bottom: 2px solid #C9A96E; padding-bottom: 15px; }
        .logo { font-size: 24px; font-weight: bold; color: #C9A96E; }
        .report-title { font-size: 22px; font-weight: bold; color: #333; margin-top: 5px; }
        .period { font-size: 11px; color: #666; margin-top: 5px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 14px; font-weight: bold; text-transform: uppercase; color: #C9A96E; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; }
        table.summary { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.summary td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; }
        table.summary td.label { color: #555; width: 65%; }
        table.summary td.value { text-align: right; font-weight: bold; width: 35%; }
        table.summary tr.highlight td { background: #f8f6f0; border-top: 2px solid #C9A96E; font-size: 14px; color: #C9A96E; }
        table.breakdown { width: 100%; border-collapse: collapse; }
        table.breakdown th { background: #f8f6f0; color: #333; font-size: 11px; text-transform: uppercase; padding: 8px; text-align: left; border-bottom: 2px solid #C9A96E; }
        table.breakdown td { padding: 8px; border-bottom: 1px solid #eee; font-size: 11px; }
        table.breakdown td.right, table.breakdown th.right { text-align: right; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">{{ $storeName ?? 'AD Perfumes' }}</div>
        <div class="report-title">UAE VAT Tax Report</div>
        <div class="period">Report #: {{ $taxReport->report_number }}</div>
        <div class="period">Period: {{ $taxReport->period_start->format('M d, Y') }} — {{ $taxReport->period_end->format('M d, Y') }}</div>
        <div class="period">Generated: {{ now()->format('M d, Y H:i') }}</div>
    </div>

    <div class="section">
        <div class="section-title">VAT Summary</div>
        <table class="summary">
            <tr>
                <td class="label">Total Sales (Inclusive of VAT)</td>
                <td class="value">AED {{ number_format($taxReport->total_sales_incl_tax, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total Sales (Exclusive of VAT)</td>
                <td class="value">AED {{ number_format($taxReport->total_sales_excl_tax, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Output VAT Collected (5%)</td>
                <td class="value">AED {{ number_format($taxReport->total_output_vat, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Commission Earned</td>
                <td class="value">AED {{ number_format($taxReport->total_commission_earned, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Commission VAT</td>
                <td class="value">AED {{ number_format($taxReport->total_commission_vat, 2) }}</td>
            </tr>
            <tr class="highlight">
                <td class="label">Net VAT Payable</td>
                <td class="value">AED {{ number_format($taxReport->net_vat_payable, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Statistics</div>
        <table class="summary">
            <tr>
                <td class="label">Total Orders</td>
                <td class="value">{{ $taxReport->total_orders }}</td>
            </tr>
            <tr>
                <td class="label">Total Merchants</td>
                <td class="value">{{ $taxReport->total_merchants }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($taxReport->merchant_breakdown))
    <div class="section">
        <div class="section-title">Merchant Breakdown</div>
        <table class="breakdown">
            <thead>
                <tr>
                    <th>Merchant</th>
                    <th class="right">Sales (AED)</th>
                    <th class="right">VAT (AED)</th>
                    <th class="right">Commission (AED)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($taxReport->merchant_breakdown as $merchant)
                <tr>
                    <td>{{ $merchant->business_name ?? $merchant['business_name'] ?? 'N/A' }}</td>
                    <td class="right">{{ number_format($merchant->total_sales ?? $merchant['total_sales'] ?? 0, 2) }}</td>
                    <td class="right">{{ number_format($merchant->vat_amount ?? $merchant['vat_amount'] ?? 0, 2) }}</td>
                    <td class="right">{{ number_format($merchant->commission ?? $merchant['commission'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(!empty($taxReport->category_breakdown))
    <div class="section">
        <div class="section-title">Category Breakdown</div>
        <table class="breakdown">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="right">Sales (AED)</th>
                    <th class="right">VAT (AED)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($taxReport->category_breakdown as $category)
                <tr>
                    <td>{{ $category->category_name ?? $category['category_name'] ?? 'N/A' }}</td>
                    <td class="right">{{ number_format($category->total_sales ?? $category['total_sales'] ?? 0, 2) }}</td>
                    <td class="right">{{ number_format($category->vat_amount ?? $category['vat_amount'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>This is a computer-generated tax report. VAT Registration applies as per UAE Federal Tax Authority regulations.</p>
        <p>{{ $storeName ?? 'AD Perfumes' }} | Generated {{ now()->format('M d, Y') }}</p>
    </div>
</body>
</html>
