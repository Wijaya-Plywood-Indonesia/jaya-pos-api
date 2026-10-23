<?php

namespace App\Filament\Resources\Ingredients;

use App\Filament\Resources\Ingredients\Pages\ManageIngredients;
use App\Models\Ingredient;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class IngredientResource extends Resource
{
    protected static ?string $model = Ingredient::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $navigationLabel = 'Daftar Bahan';

    protected static ?string $modelLabel = 'Bahan';

    protected static ?string $pluralModelLabel = 'Bahan';

    protected static ?int $navigationSort = 4;

    // Bahan milik cafe, jadi tidak ditampilkan untuk super admin
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->cafe_id !== null && $user->hasAnyRole(['owner', 'manager']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama bahan')->required()->maxLength(255),
            Select::make('unit')->label('Satuan')->required()
                ->options(['g' => 'g', 'ml' => 'ml', 'pcs' => 'pcs', 'porsi' => 'porsi']),
            TextInput::make('cost_per_unit')->label('Harga per satuan')
                ->numeric()->minValue(0)->required()->prefix('Rp'),
            TextInput::make('min_stock')->label('Stok minimum')
                ->numeric()->minValue(0)->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Bahan')->searchable()->sortable()->weight('bold'),
                TextColumn::make('unit')->label('Satuan'),
                TextColumn::make('cost_per_unit')->label('Harga/satuan')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),
                TextColumn::make('current_stock')->label('Stok')->numeric(decimalPlaces: 0),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIngredients::route('/'),
        ];
    }
}
