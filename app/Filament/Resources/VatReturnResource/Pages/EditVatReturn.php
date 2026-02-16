<?php

namespace App\Filament\Resources\VatReturnResource\Pages;

use App\Filament\Resources\VatReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVatReturn extends EditRecord
{
    protected static string $resource = VatReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->getRecord()->status === 'draft'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
