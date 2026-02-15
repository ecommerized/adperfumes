<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionRuleResource\Pages;
use App\Models\CommissionRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommissionRuleResource extends Resource
{
    protected static ?string $model = CommissionRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Commission Rule')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('level')
                            ->options(array_combine(CommissionRule::LEVELS, array_map('ucfirst', CommissionRule::LEVELS)))
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('type')
                            ->options(array_combine(CommissionRule::TYPES, array_map('ucfirst', CommissionRule::TYPES)))
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('merchant_id')
                            ->relationship('merchant', 'business_name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get): bool => $get('level') === 'merchant'),
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get): bool => $get('level') === 'category'),
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get): bool => $get('level') === 'product'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Rate Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('percentage_rate')
                            ->numeric()
                            ->suffix('%')
                            ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['percentage', 'hybrid'])),
                        Forms\Components\TextInput::make('fixed_amount')
                            ->numeric()
                            ->prefix('AED')
                            ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['fixed', 'hybrid'])),
                        Forms\Components\KeyValue::make('tier_rules')
                            ->keyLabel('Min Volume')
                            ->valueLabel('Rate %')
                            ->visible(fn (Forms\Get $get): bool => $get('type') === 'tiered'),
                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower number = higher priority'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Validity')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\DatePicker::make('valid_from'),
                        Forms\Components\DatePicker::make('valid_until'),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'global' => 'primary',
                        'merchant' => 'info',
                        'category' => 'warning',
                        'product' => 'success',
                        'tier' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('display_rate')
                    ->label('Rate'),
                Tables\Columns\TextColumn::make('merchant.business_name')
                    ->label('Merchant')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('priority')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('valid_from')
                    ->date()
                    ->placeholder('Always'),
                Tables\Columns\TextColumn::make('valid_until')
                    ->date()
                    ->placeholder('No expiry'),
            ])
            ->defaultSort('priority')
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->options(array_combine(CommissionRule::LEVELS, array_map('ucfirst', CommissionRule::LEVELS))),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissionRules::route('/'),
            'create' => Pages\CreateCommissionRule::route('/create'),
            'edit' => Pages\EditCommissionRule::route('/{record}/edit'),
        ];
    }
}
