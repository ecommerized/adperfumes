<?php

namespace App\Filament\Merchant\Widgets;

use App\Models\Settlement;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPayoutsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Recent Payouts';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Settlement::query()
                    ->where('merchant_id', auth('merchant')->id())
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Settlement #'),
                Tables\Columns\TextColumn::make('payout_date')
                    ->date(),
                Tables\Columns\TextColumn::make('total_order_amount')
                    ->label('Gross')
                    ->money('AED'),
                Tables\Columns\TextColumn::make('total_commission')
                    ->label('Commission')
                    ->money('AED')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('merchant_payout')
                    ->label('Your Payout')
                    ->money('AED')
                    ->color('success')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid On')
                    ->date()
                    ->placeholder('Pending'),
            ])
            ->paginated(false);
    }
}
