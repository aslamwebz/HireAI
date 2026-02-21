<?php

namespace App\Filament\Admin\Resources\JobApplications\Pages;

use App\Filament\Admin\Resources\JobApplications\JobApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJobApplications extends ListRecords
{
    protected static string $resource = JobApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
