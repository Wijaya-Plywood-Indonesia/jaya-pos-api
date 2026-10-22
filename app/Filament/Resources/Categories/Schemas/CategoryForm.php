<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // Hanya tampil untuk super admin (Anda), karena user cafe otomatis terisi
            Select::make('cafe_id')
                ->label('Cafe')
                ->relationship('cafe', 'name')
                ->required()
                ->visible(fn () => ! auth()->user()?->cafe_id),
            TextInput::make('name')
                ->label('Nama kategori')
                ->required()
                ->maxLength(255),
            TextInput::make('sort_order')
                ->label('Urutan')
                ->numeric()
                ->default(0),
        ]);
    }
}