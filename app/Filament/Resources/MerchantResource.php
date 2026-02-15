<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MerchantResource\Pages;
use App\Filament\Resources\MerchantResource\RelationManagers;
use App\Models\Merchant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class MerchantResource extends Resource
{
    protected static ?string $model = Merchant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Merchants';

    protected static ?string $modelLabel = 'Merchant';

    protected static ?string $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Information')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->helperText('Leave blank to keep current password'),
                    ])->columns(2),

                Forms\Components\Section::make('Business Information')
                    ->schema([
                        Forms\Components\TextInput::make('business_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contact_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('city')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('country')
                            ->required()
                            ->maxLength(255)
                            ->default('UAE'),
                    ])->columns(2),

                Forms\Components\Section::make('Business Documents')
                    ->schema([
                        Forms\Components\TextInput::make('trade_license')
                            ->label('Trade License Number')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tax_registration')
                            ->label('Tax Registration Number')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Merchant Status & Commission')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'pending' => 'Pending Approval',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'suspended' => 'Suspended',
                            ])
                            ->default('pending')
                            ->live(),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection/Suspension Reason')
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn (callable $get) => in_array($get('status'), ['rejected', 'suspended'])),

                        Forms\Components\TextInput::make('commission_percentage')
                            ->label('Commission Percentage (%)')
                            ->required()
                            ->numeric()
                            ->default(15.00)
                            ->suffix('%')
                            ->helperText('Platform commission rate for this merchant'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('contact_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->icon('heroicon-o-phone'),

                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'suspended' => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('commission_percentage')
                    ->label('Commission')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'suspended' => 'Suspended',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Merchant $record): bool => $record->status !== 'approved')
                    ->action(function (Merchant $record) {
                        $record->approve();
                        Notification::make()
                            ->success()
                            ->title('Merchant Approved')
                            ->body("Merchant {$record->business_name} has been approved.")
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn (Merchant $record): bool => $record->status === 'pending')
                    ->action(function (Merchant $record, array $data) {
                        $record->reject($data['reason']);
                        Notification::make()
                            ->danger()
                            ->title('Merchant Rejected')
                            ->body("Merchant {$record->business_name} has been rejected.")
                            ->send();
                    }),

                Tables\Actions\Action::make('suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Suspension Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn (Merchant $record): bool => $record->status === 'approved')
                    ->action(function (Merchant $record, array $data) {
                        $record->suspend($data['reason']);
                        Notification::make()
                            ->warning()
                            ->title('Merchant Suspended')
                            ->body("Merchant {$record->business_name} has been suspended.")
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductsRelationManager::class,
            RelationManagers\SettlementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMerchants::route('/'),
            'create' => Pages\CreateMerchant::route('/create'),
            'edit' => Pages\EditMerchant::route('/{record}/edit'),
        ];
    }
}
