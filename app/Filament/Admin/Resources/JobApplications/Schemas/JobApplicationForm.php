<?php

namespace App\Filament\Admin\Resources\JobApplications\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class JobApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('company')
                    ->required(),
                TextInput::make('position')
                    ->required(),
                Textarea::make('job_description')
                    ->columnSpanFull(),
                TextInput::make('job_url')
                    ->url(),
                TextInput::make('status')
                    ->required()
                    ->default('wishlist'),
                TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('salary_min')
                    ->numeric(),
                TextInput::make('salary_max')
                    ->numeric(),
                TextInput::make('location'),
                Toggle::make('remote')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                DatePicker::make('applied_at'),
                DatePicker::make('deadline_at'),
            ]);
    }
}
