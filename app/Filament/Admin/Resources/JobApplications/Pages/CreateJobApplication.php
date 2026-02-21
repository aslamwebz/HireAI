<?php

namespace App\Filament\Admin\Resources\JobApplications\Pages;

use App\Filament\Admin\Resources\JobApplications\JobApplicationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobApplication extends CreateRecord
{
    protected static string $resource = JobApplicationResource::class;
}
