<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RefundResource\Pages;
use App\Models\Refund;
use App\Services\RefundService;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RefundResource extends Resource
{
    protected static ?string $model = Refund::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 3;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Refund Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('refund_number')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('order.order_number')
                            ->label('Order #'),
                        Infolists\Components\TextEntry::make('merchant.business_name')
                            ->label('Merchant')
                            ->default('Store'),
                        Infolists\Components\TextEntry::make('type')
                            ->badge(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'info',
                                'processed' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('reason_category')
                            ->label('Reason'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Financial Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('refund_subtotal')
                            ->label('Refund Subtotal')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('refund_tax')
                            ->label('Refund Tax')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('refund_total')
                            ->label('Total Refund')
                            ->money('AED')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('commission_to_reverse')
                            ->label('Commission Reversed')
                            ->money('AED')
                            ->color('danger'),
                        Infolists\Components\IconEntry::make('is_post_settlement')
                            ->label('Post-Settlement?')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('merchant_recovery_amount')
                            ->label('Merchant Recovery')
                            ->money('AED')
                            ->visible(fn (Refund $record): bool => (bool) $record->is_post_settlement),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Refund Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->schema([
                                Infolists\Components\TextEntry::make('product_name')
                                    ->label('Product'),
                                Infolists\Components\TextEntry::make('quantity_refunded')
                                    ->label('Qty'),
                                Infolists\Components\TextEntry::make('unit_price')
                                    ->money('AED'),
                                Infolists\Components\TextEntry::make('line_refund_total')
                                    ->label('Total')
                                    ->money('AED'),
                                Infolists\Components\TextEntry::make('item_condition')
                                    ->badge(),
                                Infolists\Components\IconEntry::make('stock_restored')
                                    ->boolean(),
                            ])
                            ->columns(6),
                    ]),

                Infolists\Components\Section::make('Timeline')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Initiated')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('approved_at')
                            ->dateTime()
                            ->default('N/A'),
                        Infolists\Components\TextEntry::make('processed_at')
                            ->dateTime()
                            ->default('N/A'),
                        Infolists\Components\TextEntry::make('initiator.name')
                            ->label('Initiated By')
                            ->default('System'),
                        Infolists\Components\TextEntry::make('approver.name')
                            ->label('Approved By')
                            ->default('N/A'),
                    ])
                    ->columns(5)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('refund_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('merchant.business_name')
                    ->label('Merchant')
                    ->placeholder('Store'),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('refund_total')
                    ->label('Amount')
                    ->money('AED')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'info',
                        'processed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_post_settlement')
                    ->label('Post-Settle')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'processed' => 'Processed',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options(array_combine(Refund::TYPES, array_map('ucfirst', Refund::TYPES))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Refund $record): bool => $record->status === 'pending')
                    ->action(function (Refund $record) {
                        app(RefundService::class)->approveRefund($record, auth()->id());
                        Notification::make()->title('Refund approved')->success()->send();
                    }),
                Tables\Actions\Action::make('process')
                    ->label('Process')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalDescription('This will restore stock, create credit note, and if post-settlement, create a debit note for merchant recovery.')
                    ->visible(fn (Refund $record): bool => $record->status === 'approved')
                    ->action(function (Refund $record) {
                        app(RefundService::class)->processRefund($record);
                        Notification::make()->title('Refund processed successfully')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Refund $record): bool => $record->status === 'pending')
                    ->action(function (Refund $record) {
                        $record->update(['status' => 'rejected', 'rejected_at' => now()]);
                        Notification::make()->title('Refund rejected')->warning()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRefunds::route('/'),
            'view' => Pages\ViewRefund::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
