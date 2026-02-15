<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CreditNoteResource\Pages;
use App\Models\CreditNote;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CreditNoteResource extends Resource
{
    protected static ?string $model = CreditNote::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-minus';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 5;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Credit Note Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('credit_note_number')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('order.order_number')
                            ->label('Order #'),
                        Infolists\Components\TextEntry::make('refund.refund_number')
                            ->label('Refund #'),
                        Infolists\Components\TextEntry::make('invoice.invoice_number')
                            ->label('Original Invoice #')
                            ->default('N/A'),
                        Infolists\Components\TextEntry::make('merchant.business_name')
                            ->label('Merchant')
                            ->default('Store'),
                        Infolists\Components\TextEntry::make('type')
                            ->badge(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'issued' => 'info',
                                'applied' => 'success',
                                default => 'gray',
                            }),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Amounts')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('tax_amount')
                            ->label('VAT')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('total')
                            ->money('AED')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('reason'),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('credit_note_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('refund.refund_number')
                    ->label('Refund #'),
                Tables\Columns\TextColumn::make('merchant.business_name')
                    ->label('Merchant')
                    ->placeholder('Store'),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('total')
                    ->money('AED')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'issued' => 'info',
                        'applied' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCreditNotes::route('/'),
            'view' => Pages\ViewCreditNote::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
