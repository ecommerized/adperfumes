<?php

namespace App\Filament\Resources\SettlementResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SettlementItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Settlement Items';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_total')
                    ->money('AED'),
                Tables\Columns\TextColumn::make('order_subtotal')
                    ->label('Subtotal (excl. tax)')
                    ->money('AED'),
                Tables\Columns\TextColumn::make('commission_rate_applied')
                    ->label('Commission %')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money('AED')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('commission_tax')
                    ->label('Comm. VAT')
                    ->money('AED'),
                Tables\Columns\TextColumn::make('merchant_payout')
                    ->label('Payout')
                    ->money('AED')
                    ->color('success')
                    ->weight('bold'),
            ]);
    }
}
