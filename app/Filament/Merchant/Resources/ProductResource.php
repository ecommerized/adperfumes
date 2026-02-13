<?php

namespace App\Filament\Merchant\Resources;

use App\Filament\Merchant\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'My Products';

    protected static ?string $modelLabel = 'Product';

    /**
     * Scope products to only show the authenticated merchant's products
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('merchant_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\Select::make('brand_id')
                            ->relationship('brand', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('categories')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Select one or more categories for this product'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-generated from product name'),

                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Perfume Composition')
                    ->schema([
                        Forms\Components\Select::make('topNotes')
                            ->label('Top Notes')
                            ->relationship('notes', 'name', fn ($query) => $query->where('type', 'top'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Initial scent notes (first 15-30 minutes)'),

                        Forms\Components\Select::make('middleNotes')
                            ->label('Middle Notes (Heart)')
                            ->relationship('notes', 'name', fn ($query) => $query->where('type', 'middle'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Core fragrance (2-4 hours)'),

                        Forms\Components\Select::make('baseNotes')
                            ->label('Base Notes')
                            ->relationship('notes', 'name', fn ($query) => $query->where('type', 'base'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Long-lasting foundation notes'),

                        Forms\Components\Repeater::make('accordsData')
                            ->label('Accords')
                            ->relationship('accords')
                            ->schema([
                                Forms\Components\Select::make('accord_id')
                                    ->label('Accord')
                                    ->relationship('accords', 'name')
                                    ->searchable()
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                Forms\Components\TextInput::make('percentage')
                                    ->label('Percentage')
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->helperText('Optional: strength of this accord'),
                            ])
                            ->columns(2)
                            ->helperText('Main fragrance accords (e.g., Woody, Floral, Citrus)')
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Pricing & Inventory')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('AED')
                            ->minValue(0)
                            ->step(0.01),

                        Forms\Components\TextInput::make('stock')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\Toggle::make('status')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Product will be visible on the website'),
                    ])->columns(3),

                Forms\Components\Section::make('Product Badges')
                    ->schema([
                        Forms\Components\Toggle::make('is_new')
                            ->label('New Product Badge')
                            ->helperText('Display "NEW" badge on product cards')
                            ->default(false),

                        Forms\Components\Toggle::make('on_sale')
                            ->label('On Sale Badge')
                            ->helperText('Display "SALE" badge on product cards')
                            ->default(false)
                            ->live(),

                        Forms\Components\TextInput::make('original_price')
                            ->label('Original Price (for sale items)')
                            ->numeric()
                            ->prefix('AED')
                            ->helperText('Show strikethrough price when on sale')
                            ->visible(fn (callable $get) => $get('on_sale')),
                    ])->columns(3),

                Forms\Components\Section::make('Product Image')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Maximum file size: 2MB'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->size(50),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('brand.name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->separator(',')
                    ->searchable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('AED')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state > 10 ? 'success' : ($state > 0 ? 'warning' : 'danger')),

                Tables\Columns\IconColumn::make('status')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_new')
                    ->label('NEW')
                    ->boolean(),

                Tables\Columns\IconColumn::make('on_sale')
                    ->label('SALE')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Active')
                    ->placeholder('All products')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\TernaryFilter::make('is_new')
                    ->label('New Products'),

                Tables\Filters\TernaryFilter::make('on_sale')
                    ->label('On Sale'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
