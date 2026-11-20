<?php

namespace App\Filament\Resources\DiningTables\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DiningTableForm
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
                ->label('Nama atau nomor meja')
                ->placeholder('Contoh: Meja 1')
                ->required()
                ->maxLength(255),
            TextInput::make('capacity')
                ->label('Kapasitas (orang)')
                ->numeric()
                ->minValue(1)
                ->helperText('Boleh dikosongkan.'),
        ]);
    }
}
