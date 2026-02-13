<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Services\SettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class EmailMarketing extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Email Marketing';

    protected static string $view = 'filament.pages.email-marketing';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingsService::class);

        $this->form->fill([
            'email_provider' => $settings->get('email_provider'),
            'email_api_key' => $settings->get('email_api_key'),
            'email_from_name' => $settings->get('email_from_name'),
            'email_from_address' => $settings->get('email_from_address'),
            'email_reply_to' => $settings->get('email_reply_to'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Email Marketing Configuration')
                    ->description('Configure your email marketing provider for sending campaigns and newsletters.')
                    ->schema([
                        Forms\Components\Select::make('email_provider')
                            ->label('Email Provider')
                            ->options([
                                'mailchimp' => 'Mailchimp',
                                'sendgrid' => 'SendGrid',
                                'mailgun' => 'Mailgun',
                                'brevo' => 'Brevo (Sendinblue)',
                                'other' => 'Other / Manual',
                            ])
                            ->placeholder('Select your email provider'),

                        Forms\Components\TextInput::make('email_api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->placeholder('API key from your email provider')
                            ->maxLength(500),

                        Forms\Components\TextInput::make('email_from_name')
                            ->label('From Name')
                            ->placeholder('AD Perfumes')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email_from_address')
                            ->label('From Email Address')
                            ->email()
                            ->placeholder('marketing@adperfumes.ae')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email_reply_to')
                            ->label('Reply-To Email')
                            ->email()
                            ->placeholder('info@adperfumes.ae')
                            ->maxLength(255),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->where('marketing_email_opt_in', true)
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Customer')
                    ->searchable(['first_name', 'last_name'])
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),

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

                Tables\Columns\TextColumn::make('total_orders')
                    ->label('Orders')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->money('AED'),

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
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('exportEmails')
                    ->label('Export Email List (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $data = "Email,First Name,Last Name,Segment,Total Orders,Total Spent\n";
                        foreach ($records as $customer) {
                            $data .= implode(',', [
                                $customer->email,
                                '"' . $customer->first_name . '"',
                                '"' . $customer->last_name . '"',
                                $customer->customer_segment ?? 'new',
                                $customer->total_orders,
                                $customer->total_spent,
                            ]) . "\n";
                        }

                        return response()->streamDownload(function () use ($data) {
                            echo $data;
                        }, 'email-list-' . now()->format('Y-m-d') . '.csv');
                    }),

                Tables\Actions\BulkAction::make('exportMailchimp')
                    ->label('Export for Mailchimp')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $data = "Email Address,First Name,Last Name,Tags\n";
                        foreach ($records as $customer) {
                            $data .= implode(',', [
                                $customer->email,
                                '"' . $customer->first_name . '"',
                                '"' . $customer->last_name . '"',
                                '"' . ($customer->customer_segment ?? 'new') . '"',
                            ]) . "\n";
                        }

                        return response()->streamDownload(function () use ($data) {
                            echo $data;
                        }, 'mailchimp-import-' . now()->format('Y-m-d') . '.csv');
                    }),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(SettingsService::class);

        foreach ($data as $key => $value) {
            $settings->set($key, $value);
        }

        Notification::make()
            ->title('Email marketing settings saved successfully')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }
}
