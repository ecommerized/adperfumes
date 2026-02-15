<?php

namespace App\Filament\Resources\MerchantResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SettlementsRelationManager extends RelationManager
{
    protected static string $relationship = 'settlements';

    protected static ?string $title = 'Settlements';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#'),
                Tables\Columns\TextColumn::make('payout_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_order_amount')
                    ->label('Gross')
                    ->money('AED'),
                Tables\Columns\TextColumn::make('total_commission')
                    ->label('Commission')
                    ->money('AED')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('merchant_payout')
                    ->label('Payout')
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
                    ->dateTime('M d, Y')
                    ->placeholder('Unpaid'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
