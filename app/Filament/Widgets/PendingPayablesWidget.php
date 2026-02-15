<?php

namespace App\Filament\Widgets;

use App\Services\AccountingService;
use App\Services\SettlementService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingPayablesWidget extends BaseWidget
{
    protected static ?string $heading = 'Pending Merchant Payables';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        $service = app(AccountingService::class);
        $payables = $service->getMerchantPayablesSummary(now()->subYear(), now());
        $nextPayout = app(SettlementService::class)->getNextPayoutDate();

        return $table
            ->query(
                \App\Models\Merchant::query()
                    ->whereIn('id', array_column($payables, 'merchant_id'))
            )
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Merchant'),
                Tables\Columns\TextColumn::make('pending_amount')
                    ->label('Pending')
                    ->getStateUsing(function ($record) use ($payables) {
                        $payable = collect($payables)->firstWhere('merchant_id', $record->id);
                        return 'AED ' . number_format($payable->pending_payout ?? 0, 2);
                    }),
                Tables\Columns\TextColumn::make('eligible_orders')
                    ->label('Orders')
                    ->getStateUsing(function ($record) use ($payables) {
                        $payable = collect($payables)->firstWhere('merchant_id', $record->id);
                        return $payable->eligible_orders ?? 0;
                    }),
                Tables\Columns\TextColumn::make('next_payout')
                    ->label('Next Payout')
                    ->getStateUsing(fn () => $nextPayout->format('M d, Y')),
            ])
            ->paginated(false);
    }
}
