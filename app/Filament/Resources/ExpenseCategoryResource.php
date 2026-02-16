<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseCategoryResource\Pages;
use App\Models\ExpenseCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseCategoryResource extends Resource
{
    protected static ?string $model = ExpenseCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Expense Categories';

    protected static ?string $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 31;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(100)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                        $set('slug', \Illuminate\Support\Str::slug($state));
                                    }),

                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true),

                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(20)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('EXP-001'),

                                Forms\Components\Select::make('parent_id')
                                    ->label('Parent Category')
                                    ->relationship('parent', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),

                                Forms\Components\Select::make('type')
                                    ->required()
                                    ->options([
                                        'operational' => 'Operational',
                                        'capital' => 'Capital',
                                        'administrative' => 'Administrative',
                                        'marketing' => 'Marketing',
                                        'utilities' => 'Utilities',
                                        'hr' => 'HR',
                                        'tax' => 'Tax',
                                        'other' => 'Other',
                                    ])
                                    ->default('operational'),

                                Forms\Components\TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),

                                Forms\Components\ColorPicker::make('color')
                                    ->nullable(),

                                Forms\Components\TextInput::make('icon')
                                    ->maxLength(50)
                                    ->placeholder('heroicon-o-home')
                                    ->nullable(),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->nullable(),
                    ]),

                Forms\Components\Section::make('Tax Settings')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_vat_reclaimable')
                                    ->label('VAT Reclaimable')
                                    ->helperText('Can input VAT be reclaimed on expenses in this category?')
                                    ->default(true)
                                    ->inline(false),

                                Forms\Components\Toggle::make('is_tax_deductible')
                                    ->label('Tax Deductible')
                                    ->helperText('Are expenses in this category deductible for corporate tax?')
                                    ->default(true)
                                    ->inline(false),

                                Forms\Components\TextInput::make('default_vat_rate')
                                    ->label('Default VAT Rate (%)')
                                    ->numeric()
                                    ->default(5.00)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->suffix('%'),
                            ]),
                    ]),

                Forms\Components\Section::make('Budget & Approval')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('monthly_budget')
                                    ->label('Monthly Budget (AED)')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->nullable()
                                    ->helperText('Leave empty for no budget limit'),

                                Forms\Components\Toggle::make('requires_approval')
                                    ->label('Requires Approval')
                                    ->helperText('Do expenses in this category require manager approval?')
                                    ->default(false)
                                    ->live()
                                    ->inline(false),

                                Forms\Components\TextInput::make('approval_threshold')
                                    ->label('Approval Threshold (AED)')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->nullable()
                                    ->helperText('Auto-approve expenses below this amount')
                                    ->visible(fn (Forms\Get $get) => $get('requires_approval')),
                            ]),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
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
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'primary' => 'operational',
                        'success' => 'capital',
                        'warning' => 'utilities',
                        'danger' => 'tax',
                        'info' => 'marketing',
                    ])
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_vat_reclaimable')
                    ->label('VAT Reclaimable')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('is_tax_deductible')
                    ->label('Tax Deductible')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('monthly_budget')
                    ->label('Budget')
                    ->money('AED')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('expenses_count')
                    ->counts('expenses')
                    ->label('Expenses')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->multiple()
                    ->options([
                        'operational' => 'Operational',
                        'capital' => 'Capital',
                        'administrative' => 'Administrative',
                        'marketing' => 'Marketing',
                        'utilities' => 'Utilities',
                        'hr' => 'HR',
                        'tax' => 'Tax',
                        'other' => 'Other',
                    ]),

                Tables\Filters\TernaryFilter::make('is_vat_reclaimable')
                    ->label('VAT Reclaimable'),

                Tables\Filters\TernaryFilter::make('is_tax_deductible')
                    ->label('Tax Deductible'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc');
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
            'index' => Pages\ListExpenseCategories::route('/'),
            'create' => Pages\CreateExpenseCategory::route('/create'),
            'edit' => Pages\EditExpenseCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }
}
