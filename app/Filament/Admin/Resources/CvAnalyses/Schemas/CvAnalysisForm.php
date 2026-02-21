<?php

namespace App\Filament\Admin\Resources\CvAnalyses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CvAnalysisForm
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
                TextInput::make('match_score')
                    ->numeric(),
                TextInput::make('ats_score')
                    ->numeric(),
                Textarea::make('missing_keywords')
                    ->columnSpanFull(),
                Textarea::make('improvements')
                    ->columnSpanFull(),
                Textarea::make('rewritten_bullets')
                    ->columnSpanFull(),
                Textarea::make('strengths')
                    ->columnSpanFull(),
                Textarea::make('weaknesses')
                    ->columnSpanFull(),
                Textarea::make('full_analysis')
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
            ]);
    }
}
