<?php

namespace App\Filament\Imports;

use App\Models\Customer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CustomerImporter extends Importer
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('first_name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('last_name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('email')
                ->requiredMapping()
                ->rules(['required', 'email', 'max:255']),

            ImportColumn::make('phone')
                ->rules(['nullable', 'string', 'max:50']),

            ImportColumn::make('address')
                ->rules(['nullable', 'string', 'max:500']),

            ImportColumn::make('city')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('country')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('postal_code')
                ->rules(['nullable', 'string', 'max:20']),

            ImportColumn::make('marketing_email_opt_in')
                ->boolean()
                ->rules(['nullable', 'boolean']),

            ImportColumn::make('marketing_whatsapp_opt_in')
                ->boolean()
                ->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): ?Customer
    {
        // Update existing customer by email, or create new one
        return Customer::firstOrNew([
            'email' => $this->data['email'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your customer import has completed. ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
