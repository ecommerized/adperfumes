<?php

namespace App\Filament\Merchant\Resources;

use App\Filament\Merchant\Resources\SettlementResource\Pages;
use App\Models\Settlement;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SettlementResource extends Resource
{
    protected static ?string $model = Settlement::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'My Settlements';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('merchant_id', auth('merchant')->id());
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Settlement Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('Settlement #'),
                        Infolists\Components\TextEntry::make('payout_date')
                            ->date(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'paid' => 'success',
                                default => 'gray',
                            }),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Financial Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_order_amount')
                            ->label('Gross Sales')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('total_commission')
                            ->label('Platform Commission')
                            ->money('AED')
                            ->color('danger'),
                        Infolists\Components\TextEntry::make('merchant_payout')
                            ->label('Your Payout')
                            ->money('AED')
                            ->color('success')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Payment Info')
                    ->schema([
                        Infolists\Components\TextEntry::make('transaction_reference')
                            ->default('Pending'),
                        Infolists\Components\TextEntry::make('paid_at')
                            ->dateTime()
                            ->default('Not yet paid'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
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
                    ->date()
                    ->placeholder('Pending'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettlements::route('/'),
            'view' => Pages\ViewSettlement::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
