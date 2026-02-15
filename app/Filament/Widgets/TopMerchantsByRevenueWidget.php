<?php

namespace App\Filament\Widgets;

use App\Services\AccountingService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopMerchantsByRevenueWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Merchants This Month';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        $service = app(AccountingService::class);
        $merchants = $service->getTopMerchants(now()->startOfMonth(), now(), 5);

        return $table
            ->query(
                \App\Models\Merchant::query()
                    ->whereIn('id', array_column($merchants, 'merchant_id'))
            )
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Merchant')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_count')
                    ->label('Orders')
                    ->getStateUsing(function ($record) use ($merchants) {
                        $merchant = collect($merchants)->firstWhere('merchant_id', $record->id);
                        return $merchant->order_count ?? 0;
                    }),
                Tables\Columns\TextColumn::make('gmv')
                    ->label('GMV')
                    ->getStateUsing(function ($record) use ($merchants) {
                        $merchant = collect($merchants)->firstWhere('merchant_id', $record->id);
                        return 'AED ' . number_format($merchant->gmv ?? 0, 2);
                    }),
                Tables\Columns\TextColumn::make('commission')
                    ->label('Commission')
                    ->getStateUsing(function ($record) use ($merchants) {
                        $merchant = collect($merchants)->firstWhere('merchant_id', $record->id);
                        return 'AED ' . number_format($merchant->commission ?? 0, 2);
                    }),
            ])
            ->paginated(false);
    }
}
