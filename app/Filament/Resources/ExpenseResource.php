<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Expenses';

    protected static ?string $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 32;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Expense Details')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('expense_number')
                                    ->label('Expense #')
                                    ->disabled()
                                    ->placeholder('Auto-generated')
                                    ->visible(fn ($record) => $record !== null),

                                Forms\Components\Select::make('expense_category_id')
                                    ->label('Category')
                                    ->required()
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, ?int $state) {
                                        if ($state) {
                                            $category = ExpenseCategory::find($state);
                                            if ($category) {
                                                $set('is_vat_reclaimable', $category->is_vat_reclaimable);
                                                $set('is_tax_deductible', $category->is_tax_deductible);
                                                $set('vat_rate', $category->default_vat_rate);
                                            }
                                        }
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required(),
                                        Forms\Components\Select::make('type')
                                            ->required()
                                            ->options([
                                                'operational' => 'Operational',
                                                'capital' => 'Capital',
                                                'utilities' => 'Utilities',
                                                'hr' => 'HR',
                                                'marketing' => 'Marketing',
                                            ]),
                                    ]),

                                Forms\Components\DatePicker::make('expense_date')
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now()),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('description')
                                    ->maxLength(1000)
                                    ->columnSpanFull()
                                    ->rows(3),
                            ]),
                    ]),

                Forms\Components\Section::make('Vendor Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('vendor_name')
                                    ->required()
                                    ->maxLength(200),

                                Forms\Components\TextInput::make('vendor_trn')
                                    ->label('Vendor TRN')
                                    ->maxLength(50)
                                    ->placeholder('100123456700003')
                                    ->helperText('Tax Registration Number for VAT reclaim'),

                                Forms\Components\TextInput::make('invoice_number')
                                    ->maxLength(100),

                                Forms\Components\DatePicker::make('invoice_date')
                                    ->maxDate(now()),
                            ]),
                    ]),

                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('amount_excl_vat')
                                    ->label('Amount (Excl. VAT)')
                                    ->required()
                                    ->numeric()
                                    ->prefix('AED')
                                    ->minValue(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        $vatRate = $get('vat_rate') ?? 5.00;
                                        $vatAmount = round($state * ($vatRate / 100), 2);
                                        $total = round($state + $vatAmount, 2);

                                        $set('vat_amount', $vatAmount);
                                        $set('total_amount', $total);
                                    }),

                                Forms\Components\TextInput::make('vat_rate')
                                    ->label('VAT Rate (%)')
                                    ->numeric()
                                    ->default(5.00)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        $amount = $get('amount_excl_vat') ?? 0;
                                        $vatAmount = round($amount * ($state / 100), 2);
                                        $total = round($amount + $vatAmount, 2);

                                        $set('vat_amount', $vatAmount);
                                        $set('total_amount', $total);
                                    }),

                                Forms\Components\TextInput::make('vat_amount')
                                    ->label('VAT Amount')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total Amount')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'font-bold text-lg']),

                                Forms\Components\Select::make('currency')
                                    ->default('AED')
                                    ->options([
                                        'AED' => 'AED',
                                        'USD' => 'USD',
                                        'EUR' => 'EUR',
                                        'GBP' => 'GBP',
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Tax Treatment')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_vat_reclaimable')
                                    ->label('VAT Reclaimable')
                                    ->helperText('Can input VAT be reclaimed?')
                                    ->inline(false),

                                Forms\Components\Toggle::make('is_tax_deductible')
                                    ->label('Tax Deductible')
                                    ->helperText('Deductible for corporate tax?')
                                    ->inline(false),
                            ]),
                    ]),

                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('payment_method')
                                    ->options([
                                        'bank_transfer' => 'Bank Transfer',
                                        'cash' => 'Cash',
                                        'credit_card' => 'Credit Card',
                                        'debit_card' => 'Debit Card',
                                        'cheque' => 'Cheque',
                                        'online_payment' => 'Online Payment',
                                        'other' => 'Other',
                                    ]),

                                Forms\Components\TextInput::make('payment_reference')
                                    ->maxLength(100)
                                    ->placeholder('Transaction ID, Cheque #, etc.'),

                                Forms\Components\DatePicker::make('payment_date')
                                    ->maxDate(now()),
                            ]),
                    ])
                    ->visible(fn ($record) => $record && in_array($record->status, ['approved', 'paid'])),

                Forms\Components\Section::make('Allocation')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('merchant_id')
                                    ->label('Merchant')
                                    ->relationship('merchant', 'store_name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),

                                Forms\Components\TextInput::make('cost_center')
                                    ->maxLength(100)
                                    ->placeholder('e.g., Marketing, IT, Operations'),

                                Forms\Components\TextInput::make('project_code')
                                    ->maxLength(100)
                                    ->placeholder('Project or campaign code'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Recurring Settings')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('is_recurring')
                                    ->label('Recurring Expense')
                                    ->live()
                                    ->inline(false),

                                Forms\Components\Select::make('recurring_frequency')
                                    ->label('Frequency')
                                    ->options([
                                        'daily' => 'Daily',
                                        'weekly' => 'Weekly',
                                        'monthly' => 'Monthly',
                                        'quarterly' => 'Quarterly',
                                        'yearly' => 'Yearly',
                                    ])
                                    ->visible(fn (Forms\Get $get) => $get('is_recurring')),

                                Forms\Components\DatePicker::make('next_occurrence_date')
                                    ->label('Next Occurrence')
                                    ->visible(fn (Forms\Get $get) => $get('is_recurring')),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Depreciation (Capital Assets)')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('is_depreciable')
                                    ->label('Depreciable Asset')
                                    ->live()
                                    ->inline(false),

                                Forms\Components\TextInput::make('depreciation_rate')
                                    ->label('Depreciation Rate (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->visible(fn (Forms\Get $get) => $get('is_depreciable')),

                                Forms\Components\TextInput::make('useful_life_months')
                                    ->label('Useful Life (Months)')
                                    ->numeric()
                                    ->visible(fn (Forms\Get $get) => $get('is_depreciable')),

                                Forms\Components\TextInput::make('book_value')
                                    ->label('Current Book Value')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->disabled()
                                    ->visible(fn ($record) => $record && $record->is_depreciable),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (Forms\Get $get) => $get('expense_category_id') &&
                        ExpenseCategory::find($get('expense_category_id'))?->type === 'capital'),

                Forms\Components\Section::make('Internal Notes')
                    ->schema([
                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Admin Notes')
                            ->maxLength(1000)
                            ->rows(3)
                            ->helperText('Internal notes (not visible to vendor)'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending_approval' => 'Pending Approval',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'paid' => 'Paid',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Placeholder::make('rejection_reason')
                            ->content(fn ($record) => $record?->rejection_reason ?? '—')
                            ->visible(fn ($record) => $record && $record->status === 'rejected'),
                    ])
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_number')
                    ->label('Expense #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('expense_date')
                    ->date()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->badge(),

                Tables\Columns\TextColumn::make('vendor_name')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('AED')
                    ->sortable()
                    ->weight('bold')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('AED')
                            ->label('Total'),
                    ]),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending_approval',
                        'success' => ['approved', 'paid'],
                        'danger' => ['rejected', 'cancelled'],
                    ])
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_vat_reclaimable')
                    ->label('VAT')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('vat_reclaimed')
                    ->label('Reclaimed')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'draft' => 'Draft',
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('expense_category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('expense_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('expense_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('expense_date', '<=', $date),
                            );
                    }),

                Tables\Filters\TernaryFilter::make('is_vat_reclaimable')
                    ->label('VAT Reclaimable'),

                Tables\Filters\TernaryFilter::make('is_recurring')
                    ->label('Recurring'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Expense $record) => $record->status === 'pending_approval')
                        ->action(function (Expense $record) {
                            $record->approve(Auth::user());
                            Notification::make()
                                ->title('Expense approved')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Expense $record) => $record->status === 'pending_approval')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->required()
                                ->label('Rejection Reason'),
                        ])
                        ->action(function (Expense $record, array $data) {
                            $record->reject(Auth::user(), $data['rejection_reason']);
                            Notification::make()
                                ->title('Expense rejected')
                                ->danger()
                                ->send();
                        }),

                    Tables\Actions\Action::make('mark_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Expense $record) => $record->status === 'approved')
                        ->form([
                            Forms\Components\TextInput::make('payment_reference')
                                ->required()
                                ->label('Payment Reference'),
                            Forms\Components\Select::make('payment_method')
                                ->required()
                                ->options([
                                    'bank_transfer' => 'Bank Transfer',
                                    'cash' => 'Cash',
                                    'credit_card' => 'Credit Card',
                                    'cheque' => 'Cheque',
                                ]),
                        ])
                        ->action(function (Expense $record, array $data) {
                            $record->markAsPaid($data['payment_reference'], $data['payment_method']);
                            Notification::make()
                                ->title('Expense marked as paid')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('expense_date', 'desc');
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'view' => Pages\ViewExpense::route('/{record}'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending_approval')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
