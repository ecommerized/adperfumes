<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VatReturnResource\Pages;
use App\Models\VatReturn;
use App\Services\VatReturnService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VatReturnResource extends Resource
{
    protected static ?string $model = VatReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'VAT Returns';

    protected static ?string $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 33;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Period Information')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('return_number')
                                    ->label('Return #')
                                    ->disabled()
                                    ->visible(fn ($record) => $record !== null),

                                Forms\Components\Select::make('period_type')
                                    ->options([
                                        'quarterly' => 'Quarterly',
                                        'monthly' => 'Monthly',
                                    ])
                                    ->default('quarterly')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('year')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('quarter')
                                    ->label('Quarter')
                                    ->disabled()
                                    ->dehydrated()
                                    ->visible(fn (Forms\Get $get) => $get('period_type') === 'quarterly'),

                                Forms\Components\TextInput::make('month')
                                    ->label('Month')
                                    ->disabled()
                                    ->dehydrated()
                                    ->visible(fn (Forms\Get $get) => $get('period_type') === 'monthly'),

                                Forms\Components\DatePicker::make('period_start')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\DatePicker::make('period_end')
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                    ]),

                Forms\Components\Section::make('Output VAT (Sales)')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('total_sales_excl_vat')
                                    ->label('Total Sales (Excl. VAT)')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('output_vat_rate')
                                    ->label('VAT Rate (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('output_vat_amount')
                                    ->label('Output VAT')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'font-bold']),

                                Forms\Components\TextInput::make('zero_rated_sales')
                                    ->label('Zero-Rated Sales')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('exempt_sales')
                                    ->label('Exempt Sales')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Input VAT (Purchases)')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('total_purchases_excl_vat')
                                    ->label('Total Purchases (Excl. VAT)')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('input_vat_amount')
                                    ->label('Total Input VAT')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('input_vat_reclaimable')
                                    ->label('Reclaimable Input VAT')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'font-bold']),
                            ]),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Net VAT Calculation')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('adjustments')
                                    ->label('Adjustments')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        $outputVat = $get('output_vat_amount') ?? 0;
                                        $inputVat = $get('input_vat_reclaimable') ?? 0;
                                        $net = round($outputVat - $inputVat + $state, 2);
                                        $set('net_vat_payable', $net);
                                    }),

                                Forms\Components\Textarea::make('adjustment_notes')
                                    ->label('Adjustment Notes')
                                    ->maxLength(500)
                                    ->visible(fn (Forms\Get $get) => $get('adjustments') != 0),

                                Forms\Components\TextInput::make('net_vat_payable')
                                    ->label('Net VAT Payable')
                                    ->numeric()
                                    ->prefix('AED')
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'font-bold text-lg'])
                                    ->helperText(fn (Forms\Get $get) =>
                                        $get('net_vat_payable') < 0
                                            ? '⚠️ Negative = Refund due from FTA'
                                            : 'Positive = Amount to pay FTA'
                                    ),
                            ]),
                    ])
                    ->description('Net VAT = Output VAT - Input VAT + Adjustments'),

                Forms\Components\Section::make('Filing Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('filing_deadline')
                                    ->label('Filing Deadline')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\DatePicker::make('payment_due_date')
                                    ->label('Payment Due Date')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'pending_review' => 'Pending Review',
                                        'approved' => 'Approved',
                                        'filed' => 'Filed',
                                        'paid' => 'Paid',
                                        'refund_requested' => 'Refund Requested',
                                        'refund_received' => 'Refund Received',
                                        'amended' => 'Amended',
                                    ])
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\DatePicker::make('filed_at')
                                    ->label('Filed Date')
                                    ->disabled()
                                    ->dehydrated()
                                    ->visible(fn ($record) => $record && $record->filed_at),

                                Forms\Components\TextInput::make('fta_reference')
                                    ->label('FTA Reference')
                                    ->disabled()
                                    ->dehydrated()
                                    ->visible(fn ($record) => $record && $record->fta_reference),

                                Forms\Components\TextInput::make('payment_reference')
                                    ->label('Payment Reference')
                                    ->disabled()
                                    ->dehydrated()
                                    ->visible(fn ($record) => $record && $record->payment_reference),
                            ]),
                    ])
                    ->visible(fn ($record) => $record !== null),

                Forms\Components\Section::make('Amendment Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_amendment')
                                    ->label('Is Amendment')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\Textarea::make('amendment_reason')
                                    ->label('Amendment Reason')
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                    ])
                    ->visible(fn ($record) => $record && $record->is_amendment)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('return_number')
                    ->label('Return #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(fn ($record) =>
                        $record->period_start->format('M d') . ' - ' . $record->period_end->format('M d, Y')
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_type')
                    ->badge()
                    ->colors([
                        'primary' => 'quarterly',
                        'info' => 'monthly',
                    ]),

                Tables\Columns\TextColumn::make('output_vat_amount')
                    ->label('Output VAT')
                    ->money('AED')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('input_vat_reclaimable')
                    ->label('Input VAT')
                    ->money('AED')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('net_vat_payable')
                    ->label('Net VAT')
                    ->money('AED')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($state) => $state < 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) =>
                        ($state < 0 ? 'Refund: ' : 'Payable: ') . 'AED ' . number_format(abs($state), 2)
                    ),

                Tables\Columns\TextColumn::make('filing_deadline')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) =>
                        $record->isOverdue() ? 'danger' :
                        ($record->isDeadlineApproaching() ? 'warning' : 'gray')
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending_review',
                        'info' => 'approved',
                        'success' => ['filed', 'paid', 'refund_received'],
                        'danger' => 'amended',
                    ])
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_amendment')
                    ->label('Amended')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->options(function () {
                        $years = [];
                        for ($i = 2020; $i <= date('Y') + 1; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    }),

                Tables\Filters\SelectFilter::make('period_type')
                    ->options([
                        'quarterly' => 'Quarterly',
                        'monthly' => 'Monthly',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'draft' => 'Draft',
                        'pending_review' => 'Pending Review',
                        'approved' => 'Approved',
                        'filed' => 'Filed',
                        'paid' => 'Paid',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn (VatReturn $record) => in_array($record->status, ['draft', 'pending_review'])),

                    Tables\Actions\Action::make('submit_review')
                        ->label('Submit for Review')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('info')
                        ->requiresConfirmation()
                        ->visible(fn (VatReturn $record) => $record->status === 'draft')
                        ->action(function (VatReturn $record) {
                            $service = app(VatReturnService::class);
                            if ($service->submitForReview($record)) {
                                Notification::make()
                                    ->title('VAT return submitted for review')
                                    ->success()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (VatReturn $record) => $record->status === 'pending_review')
                        ->action(function (VatReturn $record) {
                            $service = app(VatReturnService::class);
                            if ($service->approveReturn($record, auth()->id())) {
                                Notification::make()
                                    ->title('VAT return approved')
                                    ->success()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('file')
                        ->label('File with FTA')
                        ->icon('heroicon-o-document-check')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->visible(fn (VatReturn $record) => $record->status === 'approved')
                        ->form([
                            Forms\Components\TextInput::make('fta_reference')
                                ->label('FTA Reference Number')
                                ->required()
                                ->placeholder('FTA-REF-123456'),
                        ])
                        ->action(function (VatReturn $record, array $data) {
                            $service = app(VatReturnService::class);
                            if ($service->fileReturn($record, $data['fta_reference'])) {
                                Notification::make()
                                    ->title('VAT return filed successfully')
                                    ->body('All expenses have been marked as VAT reclaimed')
                                    ->success()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('record_payment')
                        ->label('Record Payment')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (VatReturn $record) =>
                            $record->status === 'filed' && $record->net_vat_payable > 0
                        )
                        ->form([
                            Forms\Components\TextInput::make('payment_reference')
                                ->label('Payment Reference')
                                ->required()
                                ->placeholder('PAY-FTA-789012'),
                        ])
                        ->action(function (VatReturn $record, array $data) {
                            $service = app(VatReturnService::class);
                            if ($service->recordPayment($record, $data['payment_reference'])) {
                                Notification::make()
                                    ->title('Payment recorded successfully')
                                    ->success()
                                    ->send();
                            }
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (VatReturn $record) => $record->status === 'draft'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('period_start', 'desc');
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
            'index' => Pages\ListVatReturns::route('/'),
            'create' => Pages\CreateVatReturn::route('/create'),
            'view' => Pages\ViewVatReturn::route('/{record}'),
            'edit' => Pages\EditVatReturn::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $overdue = static::getModel()::overdue()->count();
        $approaching = static::getModel()::deadlineApproaching()->count();

        return $overdue > 0 ? (string) $overdue : ($approaching > 0 ? (string) $approaching : null);
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $overdue = static::getModel()::overdue()->count();
        return $overdue > 0 ? 'danger' : 'warning';
    }
}
