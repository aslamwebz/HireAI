<?php

namespace App\Filament\Admin\Resources\CvAnalyses;

use App\Filament\Admin\Resources\CvAnalyses\Pages\CreateCvAnalysis;
use App\Filament\Admin\Resources\CvAnalyses\Pages\EditCvAnalysis;
use App\Filament\Admin\Resources\CvAnalyses\Pages\ListCvAnalyses;
use App\Filament\Admin\Resources\CvAnalyses\Schemas\CvAnalysisForm;
use App\Filament\Admin\Resources\CvAnalyses\Tables\CvAnalysesTable;
use App\Models\CvAnalysis;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CvAnalysisResource extends Resource
{
    protected static ?string $model = CvAnalysis::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CvAnalysisForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CvAnalysesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCvAnalyses::route('/'),
            'create' => CreateCvAnalysis::route('/create'),
            'edit' => EditCvAnalysis::route('/{record}/edit'),
        ];
    }
}
