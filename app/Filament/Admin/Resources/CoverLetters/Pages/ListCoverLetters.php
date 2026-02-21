<?php

namespace App\Filament\Admin\Resources\CoverLetters\Pages;

use App\Filament\Admin\Resources\CoverLetters\CoverLetterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoverLetters extends ListRecords
{
    protected static string $resource = CoverLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
