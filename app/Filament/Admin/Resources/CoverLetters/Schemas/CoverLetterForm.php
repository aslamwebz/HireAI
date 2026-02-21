<?php

namespace App\Filament\Admin\Resources\CoverLetters\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CoverLetterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('job_application_id')
                    ->relationship('jobApplication', 'id'),
                Textarea::make('cv_content')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('job_description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('tone')
                    ->required()
                    ->default('professional'),
                Textarea::make('content')
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
            ]);
    }
}
