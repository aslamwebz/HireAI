<?php

namespace App\Filament\Admin\Resources\CoverLetters\Pages;

use App\Filament\Admin\Resources\CoverLetters\CoverLetterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCoverLetter extends EditRecord
{
    protected static string $resource = CoverLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
