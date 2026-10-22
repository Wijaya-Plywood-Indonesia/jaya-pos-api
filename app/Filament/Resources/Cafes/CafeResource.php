<?php

namespace App\Filament\Resources\Cafes;

use App\Filament\Resources\Cafes\Pages\CreateCafe;
use App\Filament\Resources\Cafes\Pages\EditCafe;
use App\Filament\Resources\Cafes\Pages\ListCafes;
use App\Filament\Resources\Cafes\Schemas\CafeForm;
use App\Filament\Resources\Cafes\Tables\CafesTable;
use App\Models\Cafe;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CafeResource extends Resource
{
    protected static ?string $model = Cafe::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string|UnitEnum|null $navigationGroup = 'Super Admin';

    protected static ?string $navigationLabel = 'Cafe Pelanggan';

    protected static ?string $modelLabel = 'Cafe';

    protected static ?string $pluralModelLabel = 'Cafe Pelanggan';

    protected static ?string $recordTitleAttribute = 'name';

    // Hanya super admin (user tanpa cafe) yang boleh melihat dan membuka halaman ini
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->cafe_id === null;
    }

    public static function form(Schema $schema): Schema
    {
        return CafeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CafesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCafes::route('/'),
            'create' => CreateCafe::route('/create'),
            'edit' => EditCafe::route('/{record}/edit'),
        ];
    }
}