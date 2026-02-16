<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Tax Invoice') }} - {{ $invoice->invoice_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Inter:wght@400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Cairo', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #1f2937;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }

        .logo {
            font-size: 24pt;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 9pt;
            color: #6b7280;
            line-height: 1.5;
        }

        .qr-code {
            width: 120px;
            height: 120px;
            border: 2px solid #e5e7eb;
            padding: 5px;
            background: white;
        }

        .invoice-title {
            font-size: 20pt;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .invoice-title-ar {
            font-family: 'Cairo', sans-serif;
            font-size: 16pt;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .invoice-meta {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .meta-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .meta-label {
            display: table-cell;
            width: 30%;
            font-weight: 600;
            color: #374151;
        }

        .meta-value {
            display: table-cell;
            width: 70%;
            color: #1f2937;
        }

        .parties {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }

        .party {
            display: table-cell;
            width: 50%;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .party + .party {
            padding-left: 20px;
        }

        .party-title {
            font-size: 12pt;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 8px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 5px;
        }

        .party-title-ar {
            font-family: 'Cairo', sans-serif;
            font-size: 10pt;
            color: #6b7280;
        }

        .party-info {
            font-size: 10pt;
            line-height: 1.8;
        }

        .party-info strong {
            color: #374151;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10pt;
        }

        .items-table thead {
            background: #2563eb;
            color: white;
        }

        .items-table th {
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
        }

        .items-table th.arabic {
            font-family: 'Cairo', sans-serif;
            font-size: 9pt;
            color: #dbeafe;
        }

        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table tbody tr:hover {
            background: #f9fafb;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        .totals {
            width: 60%;
            margin-left: auto;
            margin-bottom: 25px;
        }

        .total-row {
            display: table;
            width: 100%;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .total-row.grand {
            background: #f3f4f6;
            padding: 12px 10px;
            font-size: 13pt;
            font-weight: 700;
            border: 2px solid #2563eb;
            margin-top: 5px;
        }

        .total-label {
            display: table-cell;
            width: 60%;
            font-weight: 600;
        }

        .total-value {
            display: table-cell;
            width: 40%;
            text-align: right;
        }

        .vat-notice {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 9pt;
        }

        .vat-notice-ar {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            text-align: right;
            margin-top: 5px;
            color: #92400e;
        }

        .footer {
            border-top: 2px solid #e5e7eb;
            padding-top: 15px;
            font-size: 9pt;
            color: #6b7280;
            text-align: center;
        }

        .footer-ar {
            font-family: 'Cairo', sans-serif;
            margin-top: 5px;
        }

        .fta-compliance {
            background: #ecfdf5;
            border: 1px solid #10b981;
            padding: 10px;
            margin-top: 15px;
            text-align: center;
            font-size: 9pt;
            color: #065f46;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            background: #2563eb;
            color: white;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: 600;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with Logo and QR Code -->
        <div class="header">
            <div class="header-left">
                <div class="logo">{{ $storeName ?? config('app.name', 'AD Perfumes') }}</div>
                <div class="company-info">
                    <strong>{{ __('VAT Registration No.') }}:</strong> {{ $invoice->merchant_trn ?? config('company.vat_number', 'N/A') }}<br>
                    {{ config('company.address', '') }}<br>
                    {{ config('company.phone', '') }} | {{ config('company.email', '') }}
                </div>
            </div>
            <div class="header-right">
                @if(isset($qrCode) && isset($qrCode['data_url']))
                    <img src="{{ $qrCode['data_url'] }}" alt="FTA QR Code" class="qr-code">
                    <div style="font-size: 8pt; color: #6b7280; margin-top: 5px;">
                        {{ __('Scan for FTA Verification') }}<br>
                        <span style="font-family: 'Cairo', sans-serif;">امسح للتحقق من الفاتورة</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Invoice Title -->
        <div style="text-align: center; margin-bottom: 25px;">
            <div class="invoice-title">TAX INVOICE</div>
            <div class="invoice-title-ar">فاتورة ضريبية</div>
            <span class="badge">{{ $invoice->invoice_number }}</span>
        </div>

        <!-- Invoice Metadata -->
        <div class="invoice-meta">
            <div class="meta-row">
                <div class="meta-label">{{ __('Invoice Date') }} / تاريخ الفاتورة:</div>
                <div class="meta-value">{{ $invoice->created_at->format('d/m/Y H:i') }}</div>
            </div>
            @if($invoice->due_date)
            <div class="meta-row">
                <div class="meta-label">{{ __('Due Date') }} / تاريخ الاستحقاق:</div>
                <div class="meta-value">{{ $invoice->due_date->format('d/m/Y') }}</div>
            </div>
            @endif
            <div class="meta-row">
                <div class="meta-label">{{ __('Order Number') }} / رقم الطلب:</div>
                <div class="meta-value">{{ $invoice->order->order_number ?? 'N/A' }}</div>
            </div>
            <div class="meta-row">
                <div class="meta-label">{{ __('Currency') }} / العملة:</div>
                <div class="meta-value">{{ $invoice->currency }}</div>
            </div>
        </div>

        <!-- Seller and Buyer Information -->
        <div class="parties">
            <div class="party">
                <div class="party-title">
                    SELLER / <span class="party-title-ar">البائع</span>
                </div>
                <div class="party-info">
                    <strong>{{ __('Name') }}:</strong> {{ $invoice->merchant_name ?? $storeName ?? config('app.name') }}<br>
                    <strong>{{ __('VAT No.') }} / الرقم الضريبي:</strong> {{ $invoice->merchant_trn ?? config('company.vat_number', 'N/A') }}<br>
                    @if(config('company.address'))
                    <strong>{{ __('Address') }}:</strong> {{ config('company.address') }}<br>
                    @endif
                </div>
            </div>

            <div class="party">
                <div class="party-title">
                    BUYER / <span class="party-title-ar">المشتري</span>
                </div>
                <div class="party-info">
                    <strong>{{ __('Name') }}:</strong> {{ $invoice->customer_name }}<br>
                    @if($invoice->customer_email)
                    <strong>{{ __('Email') }}:</strong> {{ $invoice->customer_email }}<br>
                    @endif
                    @if($invoice->customer_phone)
                    <strong>{{ __('Phone') }}:</strong> {{ $invoice->customer_phone }}<br>
                    @endif
                    @if($invoice->customer_address)
                    <strong>{{ __('Address') }}:</strong> {{ $invoice->customer_address }}<br>
                    @endif
                </div>
            </div>
        </div>

        <!-- VAT Notice -->
        <div class="vat-notice">
            <strong>{{ __('VAT Invoice') }}</strong> - This is a tax invoice showing {{ $invoice->tax_rate }}% VAT as per UAE Federal Tax Authority regulations.
            <div class="vat-notice-ar">
                هذه فاتورة ضريبية تُظهر ضريبة القيمة المضافة بنسبة {{ $invoice->tax_rate }}٪ وفقاً لأنظمة الهيئة الاتحادية للضرائب في الإمارات.
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">
                        {{ __('Description') }}<br>
                        <span class="arabic">الوصف</span>
                    </th>
                    <th class="text-center" style="width: 10%;">
                        {{ __('Qty') }}<br>
                        <span class="arabic">الكمية</span>
                    </th>
                    <th class="text-right" style="width: 15%;">
                        {{ __('Unit Price') }}<br>
                        <span class="arabic">سعر الوحدة</span>
                    </th>
                    <th class="text-right" style="width: 10%;">
                        {{ __('VAT %') }}<br>
                        <span class="arabic">الضريبة</span>
                    </th>
                    <th class="text-right" style="width: 20%;">
                        {{ __('Total') }}<br>
                        <span class="arabic">الإجمالي</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->product_sku)
                        <br><small style="color: #6b7280;">SKU: {{ $item->product_sku }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->price, 2) }} {{ $invoice->currency }}</td>
                    <td class="text-right">{{ number_format($invoice->tax_rate, 1) }}%</td>
                    <td class="text-right"><strong>{{ number_format($item->subtotal, 2) }} {{ $invoice->currency }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="total-row">
                <div class="total-label">{{ __('Subtotal (Excl. VAT)') }} / المجموع الفرعي</div>
                <div class="total-value">{{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency }}</div>
            </div>

            @if($invoice->shipping_amount > 0)
            <div class="total-row">
                <div class="total-label">{{ __('Shipping') }} / الشحن</div>
                <div class="total-value">{{ number_format($invoice->shipping_amount, 2) }} {{ $invoice->currency }}</div>
            </div>
            @endif

            @if($invoice->discount_amount > 0)
            <div class="total-row">
                <div class="total-label">{{ __('Discount') }} / الخصم</div>
                <div class="total-value">-{{ number_format($invoice->discount_amount, 2) }} {{ $invoice->currency }}</div>
            </div>
            @endif

            <div class="total-row" style="background: #fef3c7; padding: 10px; font-weight: 700;">
                <div class="total-label">{{ __('VAT') }} ({{ $invoice->tax_rate }}%) / ضريبة القيمة المضافة</div>
                <div class="total-value">{{ number_format($invoice->tax_amount, 2) }} {{ $invoice->currency }}</div>
            </div>

            <div class="total-row grand">
                <div class="total-label">{{ __('TOTAL') }} / الإجمالي</div>
                <div class="total-value">{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</div>
            </div>
        </div>

        <!-- FTA Compliance Badge -->
        <div class="fta-compliance">
            ✓ {{ __('FTA Compliant Invoice') }} - {{ __('Includes digitally signed QR code for verification') }}<br>
            <span style="font-family: 'Cairo', sans-serif;">فاتورة متوافقة مع الهيئة الاتحادية للضرائب - تتضمن رمز QR موقع رقمياً للتحقق</span>
        </div>

        <!-- Footer -->
        <div class="footer">
            <strong>{{ __('Thank you for your business!') }}</strong><br>
            {{ __('For any queries, please contact us at') }} {{ config('company.email', 'support@adperfumes.com') }}

            <div class="footer-ar">
                <strong>شكراً لتعاملكم معنا!</strong><br>
                لأي استفسارات، يرجى التواصل معنا على {{ config('company.email', 'support@adperfumes.com') }}
            </div>

            <div style="margin-top: 15px; font-size: 8pt; color: #9ca3af;">
                {{ __('This is a computer-generated invoice and does not require a signature.') }}<br>
                <span style="font-family: 'Cairo', sans-serif;">هذه فاتورة تم إنشاؤها بواسطة الكمبيوتر ولا تتطلب توقيعاً.</span>
            </div>
        </div>
    </div>
</body>
</html>
