<?php

namespace App\Filament\Resources\VatReturnResource\Pages;

use App\Filament\Resources\VatReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVatReturn extends ViewRecord
{
    protected static string $resource = VatReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => in_array($this->getRecord()->status, ['draft', 'pending_review'])),
        ];
    }
}
