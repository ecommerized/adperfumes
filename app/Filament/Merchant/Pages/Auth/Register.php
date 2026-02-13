<?php

namespace App\Filament\Merchant\Pages\Auth;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Hash;

class Register extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Account Information')
                    ->description('Create your merchant account credentials')
                    ->schema([
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])->columns(1),

                Section::make('Business Information')
                    ->description('Tell us about your business')
                    ->schema([
                        TextInput::make('business_name')
                            ->label('Business Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Your company or store name'),

                        TextInput::make('contact_name')
                            ->label('Contact Person Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-phone'),

                        Textarea::make('address')
                            ->label('Business Address')
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('city')
                            ->label('City')
                            ->maxLength(255),

                        TextInput::make('country')
                            ->label('Country')
                            ->required()
                            ->maxLength(255)
                            ->default('UAE'),

                        TextInput::make('trade_license')
                            ->label('Trade License Number')
                            ->maxLength(255)
                            ->helperText('Optional - can be provided later'),

                        TextInput::make('tax_registration')
                            ->label('Tax Registration Number')
                            ->maxLength(255)
                            ->helperText('Optional - can be provided later'),
                    ])->columns(2),
            ]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registration successful! Your account is pending approval.';
    }

    protected function mutateFormDataBeforeRegister(array $data): array
    {
        // Set default values
        $data['status'] = 'pending';
        $data['commission_percentage'] = 15.00;
        $data['password'] = Hash::make($data['password']);

        return $data;
    }
}
