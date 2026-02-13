<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\Select::make('brand_id')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

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

                        Forms\Components\TextInput::make('gtin')
                            ->label('GTIN / Barcode')
                            ->maxLength(14)
                            ->placeholder('e.g. 3348901250634')
                            ->helperText('EAN/UPC barcode (8-14 digits). Required for Google Merchant & catalog feeds.'),

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
                Tables\Columns\TextColumn::make('brand.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->separator(',')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_new')
                    ->label('NEW')
                    ->boolean(),
                Tables\Columns\IconColumn::make('on_sale')
                    ->label('SALE')
                    ->boolean(),
                Tables\Columns\TextColumn::make('original_price')
                    ->money('AED')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
