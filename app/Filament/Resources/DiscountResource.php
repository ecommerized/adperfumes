<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountResource\Pages;
use App\Filament\Resources\DiscountResource\RelationManagers;
use App\Models\Discount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Discount Codes';

    protected static ?string $modelLabel = 'Discount Code';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Code Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->uppercase()
                            ->helperText('Enter the discount code (e.g., WELCOME10, SUMMER2026)')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(255)
                            ->rows(2)
                            ->helperText('Internal description of this discount')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Discount Details')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'percentage' => 'Percentage (%)',
                                'fixed' => 'Fixed Amount (AED)',
                            ])
                            ->default('percentage')
                            ->reactive()
                            ->helperText('Choose whether this is a percentage discount or fixed amount'),

                        Forms\Components\TextInput::make('value')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn ($get) => $get('type') === 'percentage' ? '%' : 'AED')
                            ->helperText(fn ($get) =>
                                $get('type') === 'percentage'
                                    ? 'Enter percentage (e.g., 10 for 10% off)'
                                    : 'Enter fixed amount in AED (e.g., 50 for AED 50 off)'
                            ),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Usage Limits')
                    ->schema([
                        Forms\Components\TextInput::make('max_uses')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Total number of times this code can be used (leave empty for unlimited)'),

                        Forms\Components\TextInput::make('max_uses_per_user')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->helperText('Maximum times a single customer can use this code'),

                        Forms\Components\TextInput::make('current_uses')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Current usage count (auto-incremented)'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Conditions')
                    ->schema([
                        Forms\Components\TextInput::make('min_purchase_amount')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('AED')
                            ->helperText('Minimum order amount required to use this discount (leave empty for no minimum)'),
                    ]),

                Forms\Components\Section::make('Validity Period')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->helperText('When this discount becomes active (leave empty to activate immediately)'),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->helperText('When this discount expires (leave empty for no expiry)')
                            ->after('starts_at'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->helperText('Enable or disable this discount code'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Code copied!')
                    ->tooltip('Click to copy'),

                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'percentage',
                        'primary' => 'fixed',
                    ])
                    ->formatStateUsing(fn (string $state): string =>
                        $state === 'percentage' ? 'Percentage' : 'Fixed Amount'
                    ),

                Tables\Columns\TextColumn::make('value')
                    ->sortable()
                    ->formatStateUsing(fn ($record): string =>
                        $record->type === 'percentage'
                            ? $record->value . '%'
                            : 'AED ' . number_format($record->value, 2)
                    )
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('usage')
                    ->label('Usage')
                    ->formatStateUsing(fn ($record): string =>
                        $record->current_uses . ' / ' . ($record->max_uses ?? '∞')
                    )
                    ->color(fn ($record): string =>
                        $record->max_uses && $record->current_uses >= $record->max_uses ? 'danger' : 'success'
                    ),

                Tables\Columns\TextColumn::make('min_purchase_amount')
                    ->label('Min. Purchase')
                    ->formatStateUsing(fn ($state): string =>
                        $state ? 'AED ' . number_format($state, 2) : 'No min.'
                    )
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn ($record): string => $record->status)
                    ->colors([
                        'success' => 'Active',
                        'warning' => 'Scheduled',
                        'danger' => 'Expired',
                        'secondary' => 'Maxed Out',
                    ]),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Enabled')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed Amount',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All discounts')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\Filter::make('active_now')
                    ->query(fn (Builder $query): Builder => $query->active())
                    ->label('Currently Active'),

                Tables\Filters\Filter::make('available')
                    ->query(fn (Builder $query): Builder => $query->available())
                    ->label('Still Available'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'edit' => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
