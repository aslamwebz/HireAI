<?php

namespace App\Filament\Admin\Resources\CoverLetters;

use App\Filament\Admin\Resources\CoverLetters\Pages\CreateCoverLetter;
use App\Filament\Admin\Resources\CoverLetters\Pages\EditCoverLetter;
use App\Filament\Admin\Resources\CoverLetters\Pages\ListCoverLetters;
use App\Filament\Admin\Resources\CoverLetters\Schemas\CoverLetterForm;
use App\Filament\Admin\Resources\CoverLetters\Tables\CoverLettersTable;
use App\Models\CoverLetter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CoverLetterResource extends Resource
{
    protected static ?string $model = CoverLetter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CoverLetterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CoverLettersTable::configure($table);
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
            'index' => ListCoverLetters::route('/'),
            'create' => CreateCoverLetter::route('/create'),
            'edit' => EditCoverLetter::route('/{record}/edit'),
        ];
    }
}
