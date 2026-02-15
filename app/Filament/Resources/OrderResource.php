<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Services\RefundService;
use App\Services\Shipping\AramexService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                                'return_requested' => 'Return Requested',
                                'return_approved' => 'Return Approved',
                                'return_rejected' => 'Return Rejected',
                                'returned' => 'Returned',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('payment_method')
                            ->disabled(),
                        Forms\Components\TextInput::make('payment_id')
                            ->disabled(),
                        Forms\Components\TextInput::make('tracking_number')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('aramex_shipment_id')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')->disabled(),
                        Forms\Components\TextInput::make('last_name')->disabled(),
                        Forms\Components\TextInput::make('email')->disabled(),
                        Forms\Components\TextInput::make('phone')->disabled(),
                        Forms\Components\TextInput::make('address')->disabled(),
                        Forms\Components\TextInput::make('city')->disabled(),
                        Forms\Components\TextInput::make('country')->disabled(),
                        Forms\Components\TextInput::make('postal_code')->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('customer_notes')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Order Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('order_number')
                            ->label('Order #')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'processing' => 'info',
                                'shipped' => 'primary',
                                'delivered' => 'success',
                                'cancelled' => 'danger',
                                'refunded' => 'gray',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('payment_status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                'refunded' => 'gray',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('payment_method')
                            ->formatStateUsing(fn (?string $state) => ucfirst($state ?? 'N/A')),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Order Date')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('tracking_number')
                            ->default('N/A'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Customer')
                    ->schema([
                        Infolists\Components\TextEntry::make('full_name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('email'),
                        Infolists\Components\TextEntry::make('phone'),
                        Infolists\Components\TextEntry::make('address'),
                        Infolists\Components\TextEntry::make('city'),
                        Infolists\Components\TextEntry::make('country'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Order Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->schema([
                                Infolists\Components\TextEntry::make('product_name')
                                    ->label('Product'),
                                Infolists\Components\TextEntry::make('merchant.business_name')
                                    ->label('Merchant')
                                    ->default('Store-owned'),
                                Infolists\Components\TextEntry::make('quantity'),
                                Infolists\Components\TextEntry::make('price')
                                    ->money('AED'),
                                Infolists\Components\TextEntry::make('subtotal')
                                    ->money('AED'),
                                Infolists\Components\TextEntry::make('commission_rate')
                                    ->label('Commission %')
                                    ->formatStateUsing(fn ($state) => $state !== null ? $state . '%' : '-'),
                                Infolists\Components\TextEntry::make('commission_amount')
                                    ->label('Commission')
                                    ->money('AED'),
                            ])
                            ->columns(7),
                    ]),

                Infolists\Components\Section::make('Totals & Commission')
                    ->schema([
                        Infolists\Components\TextEntry::make('subtotal')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('shipping')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('discount')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('grand_total')
                            ->money('AED')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('total_commission')
                            ->label('Total Commission')
                            ->money('AED')
                            ->color('danger'),
                        Infolists\Components\TextEntry::make('merchant_payout')
                            ->label('Merchant Payout')
                            ->money('AED')
                            ->color('success'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Delivery & Settlement')
                    ->schema([
                        Infolists\Components\TextEntry::make('delivered_at')
                            ->dateTime()
                            ->default('Not delivered'),
                        Infolists\Components\TextEntry::make('settlement_eligible_at')
                            ->label('Settlement Eligible')
                            ->dateTime()
                            ->default('N/A'),
                        Infolists\Components\IconEntry::make('is_refund_eligible')
                            ->label('Refund Eligible')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('cancelled_at')
                            ->dateTime()
                            ->default('N/A')
                            ->visible(fn (Order $record): bool => $record->status === 'cancelled'),
                        Infolists\Components\TextEntry::make('cancellation_reason')
                            ->default('N/A')
                            ->visible(fn (Order $record): bool => $record->status === 'cancelled'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('customer_notes')
                            ->default('None'),
                        Infolists\Components\TextEntry::make('admin_notes')
                            ->default('None'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Customer')
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('grand_total')
                    ->money('AED')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_commission')
                    ->label('Commission')
                    ->money('AED')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->withSum('items', 'commission_amount')
                            ->orderBy('items_sum_commission_amount', $direction);
                    }),
                Tables\Columns\TextColumn::make('payment_method')
                    ->formatStateUsing(fn (?string $state) => ucfirst($state ?? 'N/A'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'processing' => 'info',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('tracking_number')
                    ->label('AWB #')
                    ->searchable()
                    ->copyable()
                    ->placeholder('Not generated')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                        'return_requested' => 'Return Requested',
                        'return_approved' => 'Return Approved',
                        'returned' => 'Returned',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('generateAwb')
                    ->label('Generate AWB')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Aramex AWB')
                    ->modalDescription('This will create a shipment with Aramex and generate an AWB (Air Waybill) for this order.')
                    ->visible(fn (Order $record): bool =>
                        empty($record->tracking_number) &&
                        in_array($record->status, ['confirmed', 'processing']) &&
                        in_array($record->payment_status, ['paid', 'cod_pending'])
                    )
                    ->action(function (Order $record) {
                        $aramexService = app(AramexService::class);

                        $shipmentData = [
                            'order_number' => $record->order_number,
                            'full_name' => $record->full_name,
                            'email' => $record->email,
                            'phone' => $record->phone,
                            'address' => $record->address,
                            'city' => $record->city,
                            'country' => $record->country,
                            'postal_code' => $record->postal_code,
                        ];

                        $result = $aramexService->createShipment($shipmentData);

                        if ($result['success']) {
                            $record->update([
                                'tracking_number' => $result['tracking_number'],
                                'aramex_shipment_id' => $result['aramex_shipment_id'] ?? $result['tracking_number'],
                                'status' => 'processing',
                            ]);

                            Notification::make()
                                ->title('AWB Generated Successfully')
                                ->body('Tracking #: ' . $result['tracking_number'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('AWB Generation Failed')
                                ->body($result['message'] ?? 'Unknown error occurred')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('printLabel')
                    ->label('Print Label')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->visible(fn (Order $record): bool => !empty($record->tracking_number))
                    ->url(fn (Order $record): string => 'https://www.aramex.com/track/results?ShipmentNumber=' . $record->tracking_number)
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('confirmDelivery')
                    ->label('Confirm Delivery')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This will mark the order as delivered, set settlement eligibility (15 days), and generate invoices.')
                    ->visible(fn (Order $record): bool => $record->status === 'shipped')
                    ->action(function (Order $record) {
                        $record->markDelivered();
                        Notification::make()
                            ->title('Order marked as delivered')
                            ->body('Settlement eligible on: ' . $record->fresh()->settlement_eligible_at->format('M d, Y'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('initiateRefund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Initiate Full Refund')
                    ->modalDescription('This will create a full refund for all items in this order.')
                    ->visible(fn (Order $record): bool =>
                        in_array($record->status, ['delivered', 'confirmed', 'processing']) &&
                        $record->payment_status === 'paid' &&
                        !$record->refunds()->whereIn('status', ['pending', 'approved', 'processed'])->exists()
                    )
                    ->action(function (Order $record) {
                        $items = $record->items->map(fn ($item) => [
                            'order_item_id' => $item->id,
                            'quantity' => $item->quantity,
                        ])->toArray();

                        app(RefundService::class)->createRefund(
                            $record, $items, 'full',
                            initiatedBy: auth()->id(),
                            reasonCategory: 'admin_initiated'
                        );

                        Notification::make()
                            ->title('Refund initiated')
                            ->body('A full refund has been created and is pending approval.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
