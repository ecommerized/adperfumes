<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettlementResource\Pages;
use App\Filament\Resources\SettlementResource\RelationManagers;
use App\Models\Settlement;
use App\Services\SettlementService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettlementResource extends Resource
{
    protected static ?string $model = Settlement::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 2;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Settlement Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('Settlement #'),
                        Infolists\Components\TextEntry::make('merchant.business_name')
                            ->label('Merchant'),
                        Infolists\Components\TextEntry::make('payout_date')
                            ->date(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'processing' => 'info',
                                'paid' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Financial Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_order_amount')
                            ->label('Gross Order Amount')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('total_subtotal')
                            ->label('Subtotal (excl. tax)')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('total_tax')
                            ->label('VAT')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('commission_amount')
                            ->label('Commission')
                            ->money('AED')
                            ->color('danger'),
                        Infolists\Components\TextEntry::make('commission_tax')
                            ->label('Commission VAT')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('total_commission')
                            ->label('Total Commission')
                            ->money('AED')
                            ->color('danger')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('merchant_payout')
                            ->label('Merchant Payout')
                            ->money('AED')
                            ->color('success')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Payment Info')
                    ->schema([
                        Infolists\Components\TextEntry::make('transaction_reference')
                            ->default('N/A'),
                        Infolists\Components\TextEntry::make('paid_at')
                            ->dateTime()
                            ->default('Not yet paid'),
                    ])
                    ->columns(2)
                    ->visible(fn (Settlement $record): bool => $record->status === 'paid'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('merchant.business_name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payout_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_order_amount')
                    ->label('Gross')
                    ->money('AED')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_commission')
                    ->label('Commission')
                    ->money('AED')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('merchant_payout')
                    ->label('Payout')
                    ->money('AED')
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime('M d, Y')
                    ->placeholder('Unpaid')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('merchant_id')
                    ->relationship('merchant', 'business_name')
                    ->searchable()
                    ->preload()
                    ->label('Merchant'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('markAsPaid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('Transaction Reference / Bank Ref')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->visible(fn (Settlement $record): bool => $record->status === 'pending')
                    ->action(function (Settlement $record, array $data) {
                        app(SettlementService::class)->markAsPaid($record, $data['transaction_reference']);

                        Notification::make()
                            ->title('Settlement marked as paid')
                            ->body('Transaction ref: ' . $data['transaction_reference'])
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SettlementItemsRelationManager::class,
        ];
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
