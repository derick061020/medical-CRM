<?php

namespace App\Filament\Resources\LaboratoryResultResource\Pages;

use App\Filament\Resources\LaboratoryResultResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaboratoryResults extends ListRecords
{
    protected static string $resource = LaboratoryResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
