<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayoutReportResource\Pages;
use App\Models\PayoutReport;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PayoutReportResource extends Resource
{
    protected static ?string $model = PayoutReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 3;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Report Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('report_number')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('settlement.id')
                            ->label('Settlement #'),
                        Infolists\Components\TextEntry::make('merchant.business_name')
                            ->label('Merchant'),
                        Infolists\Components\TextEntry::make('payout_date')
                            ->date(),
                        Infolists\Components\TextEntry::make('period_start')
                            ->date(),
                        Infolists\Components\TextEntry::make('period_end')
                            ->date(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge(),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Financial Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_orders')
                            ->label('Total Orders'),
                        Infolists\Components\TextEntry::make('gross_revenue')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('total_tax_collected')
                            ->label('VAT Collected')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('total_commission')
                            ->money('AED')
                            ->color('danger'),
                        Infolists\Components\TextEntry::make('commission_tax')
                            ->label('Commission VAT')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('net_payout')
                            ->money('AED')
                            ->color('success')
                            ->weight('bold'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('report_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('merchant.business_name')
                    ->label('Merchant')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payout_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_orders'),
                Tables\Columns\TextColumn::make('gross_revenue')
                    ->money('AED'),
                Tables\Columns\TextColumn::make('net_payout')
                    ->money('AED')
                    ->color('success')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'generated' => 'info',
                        'sent' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('merchant_id')
                    ->relationship('merchant', 'business_name')
                    ->searchable()
                    ->preload()
                    ->label('Merchant'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayoutReports::route('/'),
            'view' => Pages\ViewPayoutReport::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
