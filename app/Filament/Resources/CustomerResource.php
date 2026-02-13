<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 1;

    // No form - customers are auto-created from orders
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Customer')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-phone'),

                Tables\Columns\TextColumn::make('total_orders')
                    ->label('Orders')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->money('AED')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_segment')
                    ->label('Segment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'vip' => 'success',
                        'regular' => 'primary',
                        'new' => 'info',
                        'inactive' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('marketing_email_opt_in')
                    ->label('Email')
                    ->boolean()
                    ->tooltip('Email marketing opt-in'),

                Tables\Columns\IconColumn::make('marketing_whatsapp_opt_in')
                    ->label('WhatsApp')
                    ->boolean()
                    ->tooltip('WhatsApp marketing opt-in'),

                Tables\Columns\TextColumn::make('last_order_at')
                    ->label('Last Order')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('last_order_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('customer_segment')
                    ->options([
                        'vip' => 'VIP Customers',
                        'regular' => 'Regular Customers',
                        'new' => 'New Customers',
                        'inactive' => 'Inactive Customers',
                    ]),

                Tables\Filters\TernaryFilter::make('marketing_email_opt_in')
                    ->label('Email Marketing')
                    ->placeholder('All customers')
                    ->trueLabel('Opted in')
                    ->falseLabel('Opted out'),

                Tables\Filters\TernaryFilter::make('marketing_whatsapp_opt_in')
                    ->label('WhatsApp Marketing')
                    ->placeholder('All customers')
                    ->trueLabel('Opted in')
                    ->falseLabel('Opted out'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('exportEmail')
                    ->label('Export Email List')
                    ->icon('heroicon-o-envelope')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $emails = $records->where('marketing_email_opt_in', true)
                            ->pluck('email')
                            ->join(', ');

                        return response()->streamDownload(function () use ($emails) {
                            echo $emails;
                        }, 'email-list-' . now()->format('Y-m-d') . '.txt');
                    }),

                Tables\Actions\BulkAction::make('exportWhatsApp')
                    ->label('Export WhatsApp Numbers')
                    ->icon('heroicon-o-phone')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $phones = $records->where('marketing_whatsapp_opt_in', true)
                            ->whereNotNull('phone')
                            ->pluck('phone')
                            ->join("\n");

                        return response()->streamDownload(function () use ($phones) {
                            echo $phones;
                        }, 'whatsapp-list-' . now()->format('Y-m-d') . '.txt');
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Customer Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('full_name'),
                        Infolists\Components\TextEntry::make('email')
                            ->copyable()
                            ->icon('heroicon-o-envelope'),
                        Infolists\Components\TextEntry::make('phone')
                            ->copyable()
                            ->icon('heroicon-o-phone'),
                        Infolists\Components\TextEntry::make('address'),
                        Infolists\Components\TextEntry::make('city'),
                        Infolists\Components\TextEntry::make('country'),
                    ])->columns(3),

                Infolists\Components\Section::make('Marketing Preferences')
                    ->schema([
                        Infolists\Components\IconEntry::make('marketing_email_opt_in')
                            ->label('Email Marketing')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('marketing_whatsapp_opt_in')
                            ->label('WhatsApp Marketing')
                            ->boolean(),
                    ])->columns(2),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_orders')
                            ->badge(),
                        Infolists\Components\TextEntry::make('total_spent')
                            ->money('AED'),
                        Infolists\Components\TextEntry::make('customer_segment')
                            ->badge(),
                        Infolists\Components\TextEntry::make('first_order_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('last_order_at')
                            ->dateTime(),
                    ])->columns(3),

                Infolists\Components\Section::make('Order History')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('orders')
                            ->schema([
                                Infolists\Components\TextEntry::make('order_number'),
                                Infolists\Components\TextEntry::make('grand_total')
                                    ->money('AED'),
                                Infolists\Components\TextEntry::make('status')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->dateTime(),
                            ])
                            ->columns(4),
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
            'index' => Pages\ListCustomers::route('/'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
