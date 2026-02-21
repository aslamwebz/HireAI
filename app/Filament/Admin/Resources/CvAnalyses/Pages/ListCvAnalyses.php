<?php

namespace App\Filament\Admin\Resources\CvAnalyses\Pages;

use App\Filament\Admin\Resources\CvAnalyses\CvAnalysisResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCvAnalyses extends ListRecords
{
    protected static string $resource = CvAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
