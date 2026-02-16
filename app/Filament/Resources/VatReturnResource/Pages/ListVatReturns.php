<?php

namespace App\Filament\Resources\VatReturnResource\Pages;

use App\Filament\Resources\VatReturnResource;
use App\Services\VatReturnService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVatReturns extends ListRecords
{
    protected static string $resource = VatReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('prepare_return')
                ->label('Prepare VAT Return')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->form([
                    Forms\Components\Select::make('period_type')
                        ->label('Period Type')
                        ->options([
                            'quarterly' => 'Quarterly',
                            'monthly' => 'Monthly',
                        ])
                        ->default('quarterly')
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('year')
                        ->label('Year')
                        ->options(function () {
                            $years = [];
                            for ($i = date('Y') - 2; $i <= date('Y') + 1; $i++) {
                                $years[$i] = $i;
                            }
                            return $years;
                        })
                        ->default(date('Y'))
                        ->required(),

                    Forms\Components\Select::make('quarter')
                        ->label('Quarter')
                        ->options([
                            1 => 'Q1 (Jan - Mar)',
                            2 => 'Q2 (Apr - Jun)',
                            3 => 'Q3 (Jul - Sep)',
                            4 => 'Q4 (Oct - Dec)',
                        ])
                        ->default(now()->quarter)
                        ->required()
                        ->visible(fn (Forms\Get $get) => $get('period_type') === 'quarterly'),

                    Forms\Components\Select::make('month')
                        ->label('Month')
                        ->options([
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                        ])
                        ->default(now()->month)
                        ->required()
                        ->visible(fn (Forms\Get $get) => $get('period_type') === 'monthly'),
                ])
                ->action(function (array $data) {
                    $service = app(VatReturnService::class);

                    try {
                        if ($data['period_type'] === 'quarterly') {
                            $vatReturn = $service->prepareQuarterlyReturn($data['year'], $data['quarter']);
                        } else {
                            $vatReturn = $service->prepareMonthlyReturn($data['year'], $data['month']);
                        }

                        Notification::make()
                            ->title('VAT return prepared successfully')
                            ->body("Return #{$vatReturn->return_number} has been created")
                            ->success()
                            ->send();

                        $this->redirect(VatReturnResource::getUrl('view', ['record' => $vatReturn]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error preparing VAT return')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),

            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft')),

            'pending_review' => Tab::make('Pending Review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending_review'))
                ->badge(fn () => static::getModel()::where('status', 'pending_review')->count())
                ->badgeColor('warning'),

            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),

            'filed' => Tab::make('Filed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'filed')),

            'overdue' => Tab::make('Overdue')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('filing_deadline', '<', now())
                    ->whereNotIn('status', ['filed', 'paid', 'refund_received']))
                ->badge(fn () => static::getModel()::overdue()->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VatReturnResource\Widgets\VatSummaryWidget::class,
        ];
    }
}
