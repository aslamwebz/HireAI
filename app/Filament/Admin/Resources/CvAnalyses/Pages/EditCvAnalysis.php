<?php

namespace App\Filament\Admin\Resources\CvAnalyses\Pages;

use App\Filament\Admin\Resources\CvAnalyses\CvAnalysisResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCvAnalysis extends EditRecord
{
    protected static string $resource = CvAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
