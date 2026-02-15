<?php

namespace App\Filament\Pages;

use App\Models\Merchant;
use App\Services\AccountingService;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class AccountingReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $title = 'Accounting Reports';

    protected static string $view = 'filament.pages.accounting-reports';

    public string $activeTab = 'pl';
    public string $reportFrom = '';
    public string $reportTo = '';
    public ?int $selectedMerchantId = null;

    // Report data
    public ?array $plData = null;
    public ?array $taxData = null;
    public ?array $merchantData = null;
    public ?array $reconciliationData = null;

    // Download paths
    public ?string $downloadPath = null;

    public function mount(): void
    {
        $this->reportFrom = now()->startOfMonth()->format('Y-m-d');
        $this->reportTo = now()->format('Y-m-d');
    }

    public function generateProfitLoss(): void
    {
        $service = app(AccountingService::class);
        $from = Carbon::parse($this->reportFrom);
        $to = Carbon::parse($this->reportTo);

        $this->plData = $service->getProfitAndLoss($from, $to);

        // Generate PDF
        $settings = app(SettingsService::class);
        $pdf = Pdf::loadView('pdf.profit-loss', [
            'data' => $this->plData,
            'storeName' => $settings->get('store_name', 'AD Perfumes'),
        ]);

        $path = 'reports/pl-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf';
        Storage::put($path, $pdf->output());
        $this->downloadPath = $path;
    }

    public function generateTaxReport(): void
    {
        $service = app(AccountingService::class);
        $from = Carbon::parse($this->reportFrom);
        $to = Carbon::parse($this->reportTo);

        $taxReport = $service->generateTaxReport($from, $to, 'custom');

        $this->taxData = [
            'report_number' => $taxReport->report_number,
            'total_sales_incl_tax' => (float) $taxReport->total_sales_incl_tax,
            'total_sales_excl_tax' => (float) $taxReport->total_sales_excl_tax,
            'total_output_vat' => (float) $taxReport->total_output_vat,
            'total_commission_earned' => (float) $taxReport->total_commission_earned,
            'total_commission_vat' => (float) $taxReport->total_commission_vat,
            'net_vat_payable' => (float) $taxReport->net_vat_payable,
            'total_orders' => $taxReport->total_orders,
            'total_merchants' => $taxReport->total_merchants,
        ];

        // Generate PDF
        $settings = app(SettingsService::class);
        $pdf = Pdf::loadView('pdf.tax-report', [
            'taxReport' => $taxReport,
            'storeName' => $settings->get('store_name', 'AD Perfumes'),
        ]);

        $path = 'reports/tax-' . $taxReport->report_number . '.pdf';
        Storage::put($path, $pdf->output());
        $taxReport->update(['pdf_path' => $path]);
        $this->downloadPath = $path;
    }

    public function generateMerchantStatement(): void
    {
        if (!$this->selectedMerchantId) {
            return;
        }

        $service = app(AccountingService::class);
        $merchant = Merchant::findOrFail($this->selectedMerchantId);
        $from = Carbon::parse($this->reportFrom);
        $to = Carbon::parse($this->reportTo);

        $data = $service->getMerchantStatement($merchant, $from, $to);

        $this->merchantData = [
            'merchant_name' => $merchant->business_name,
            'order_count' => $data['order_count'],
            'total_gmv' => $data['total_gmv'],
            'total_commission' => $data['total_commission'],
            'net_earnings' => $data['net_earnings'],
            'total_refunds' => $data['total_refunds'],
            'total_settled' => $data['total_settled'],
            'outstanding_balance' => $data['outstanding_balance'],
        ];

        // Generate PDF
        $settings = app(SettingsService::class);
        $pdf = Pdf::loadView('pdf.merchant-statement', [
            'data' => $data,
            'storeName' => $settings->get('store_name', 'AD Perfumes'),
        ]);

        $path = 'reports/merchant-statement-' . $merchant->id . '-' . $from->format('Ymd') . '.pdf';
        Storage::put($path, $pdf->output());
        $this->downloadPath = $path;
    }

    public function generateReconciliation(): void
    {
        $service = app(AccountingService::class);
        $from = Carbon::parse($this->reportFrom);
        $to = Carbon::parse($this->reportTo);

        $reconciliation = $service->generateReconciliation($from, $to);

        $this->reconciliationData = [
            'reconciliation_number' => $reconciliation->reconciliation_number,
            'total_orders' => $reconciliation->total_orders,
            'total_gmv' => (float) $reconciliation->total_gmv,
            'total_commission_earned' => (float) $reconciliation->total_commission_earned,
            'total_tax_collected' => (float) $reconciliation->total_tax_collected,
            'total_refunds_issued' => (float) $reconciliation->total_refunds_issued,
            'total_settlements_paid' => (float) $reconciliation->total_settlements_paid,
            'total_debit_notes' => (float) $reconciliation->total_debit_notes,
            'net_platform_revenue' => (float) $reconciliation->net_platform_revenue,
            'discrepancy_amount' => (float) $reconciliation->discrepancy_amount,
            'discrepancy_notes' => $reconciliation->discrepancy_notes,
            'status' => $reconciliation->status,
        ];

        // Generate PDF
        $settings = app(SettingsService::class);
        $pdf = Pdf::loadView('pdf.reconciliation-report', [
            'reconciliation' => $reconciliation,
            'storeName' => $settings->get('store_name', 'AD Perfumes'),
        ]);

        $path = 'reports/reconciliation-' . $reconciliation->reconciliation_number . '.pdf';
        Storage::put($path, $pdf->output());
        $reconciliation->update(['pdf_path' => $path]);
        $this->downloadPath = $path;
    }

    public function downloadReport()
    {
        if ($this->downloadPath && Storage::exists($this->downloadPath)) {
            return response()->streamDownload(function () {
                echo Storage::get($this->downloadPath);
            }, basename($this->downloadPath), [
                'Content-Type' => 'application/pdf',
            ]);
        }
    }

    public function getMerchantsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return Merchant::where('status', 'approved')->orderBy('business_name')->get();
    }
}
