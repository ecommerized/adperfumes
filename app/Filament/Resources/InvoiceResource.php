<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 4;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Invoice Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('invoice_number')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('order.order_number')
                            ->label('Order #'),
                        Infolists\Components\TextEntry::make('merchant.business_name')
                            ->label('Merchant')
                            ->default('Store'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'issued' => 'info',
                                'sent' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Date')
                            ->date(),
                    ])
                    ->columns(5),

                Infolists\Components\Section::make('Customer')
                    ->schema([
                        Infolists\Components\TextEntry::make('customer_name'),
                        Infolists\Components\TextEntry::make('customer_email'),
                        Infolists\Components\TextEntry::make('customer_phone'),
                        Infolists\Components\TextEntry::make('customer_address'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Merchant')
                    ->schema([
                        Infolists\Components\TextEntry::make('merchant_name'),
                        Infolists\Components\TextEntry::make('merchant_trn')
                            ->label('TRN'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->schema([
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Item'),
                                Infolists\Components\TextEntry::make('sku'),
                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Qty'),
                                Infolists\Components\TextEntry::make('unit_price')
                                    ->label('Price (excl.)')
                                    ->money('AED'),
                                Infolists\Components\TextEntry::make('tax_amount')
                                    ->label('VAT')
                                    ->money('AED'),
                                Infolists\Components\TextEntry::make('line_total')
                                    ->label('Total')
                                    ->money('AED'),
                            ])
                            ->columns(6),
                    ]),

                Infolists\Components\Section::make('Totals')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->label('Subtotal (excl. tax)')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('tax_amount')
                            ->label('VAT (5%)')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('total')
                            ->label('Grand Total')
                            ->money('AED')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('commission_amount')
                            ->label('Commission')
                            ->money('AED')
                            ->color('danger'),
                        Infolists\Components\TextEntry::make('net_merchant_amount')
                            ->label('Net to Merchant')
                            ->money('AED')
                            ->color('success'),
                    ])
                    ->columns(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('merchant.business_name')
                    ->label('Merchant')
                    ->placeholder('Store'),
                Tables\Columns\TextColumn::make('customer_name'),
                Tables\Columns\TextColumn::make('total')
                    ->money('AED')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'issued' => 'info',
                        'sent' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'issued' => 'Issued',
                        'sent' => 'Sent',
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
                Tables\Actions\Action::make('markAsSent')
                    ->label('Mark as Sent')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (Invoice $record): bool => $record->status === 'issued')
                    ->action(function (Invoice $record) {
                        $record->update(['status' => 'sent']);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
