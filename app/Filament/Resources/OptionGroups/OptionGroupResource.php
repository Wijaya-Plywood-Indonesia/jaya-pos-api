<?php

namespace App\Filament\Resources\OptionGroups;

use App\Filament\Resources\OptionGroups\Pages\CreateOptionGroup;
use App\Filament\Resources\OptionGroups\Pages\EditOptionGroup;
use App\Filament\Resources\OptionGroups\Pages\ListOptionGroups;
use App\Filament\Resources\OptionGroups\Schemas\OptionGroupForm;
use App\Filament\Resources\OptionGroups\Tables\OptionGroupsTable;
use App\Models\OptionGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class OptionGroupResource extends Resource
{
    protected static ?string $model = OptionGroup::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Opsi / Modifier';

    protected static ?string $modelLabel = 'Grup opsi';

    protected static ?string $pluralModelLabel = 'Opsi / Modifier';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return OptionGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OptionGroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOptionGroups::route('/'),
            'create' => CreateOptionGroup::route('/create'),
            'edit' => EditOptionGroup::route('/{record}/edit'),
        ];
    }
}