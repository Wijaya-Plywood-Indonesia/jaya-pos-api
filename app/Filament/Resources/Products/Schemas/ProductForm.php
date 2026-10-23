<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data menu')
                ->columns(2)
                ->schema([
                    Select::make('cafe_id')
                        ->label('Cafe')
                        ->relationship('cafe', 'name')
                        ->required()
                        ->visible(fn () => ! auth()->user()?->cafe_id)
                        ->columnSpanFull(),
                    SpatieMediaLibraryFileUpload::make('photo')
                        ->label('Foto menu')
                        ->collection('photo')
                        ->conversion('thumb')
                        ->image()
                        ->imageEditor()
                        ->maxSize(2048)
                        ->helperText('JPG, PNG, atau WebP, maksimal 2 MB. Foto persegi paling bagus.')
                        ->columnSpanFull(),
                    TextInput::make('name')
                        ->label('Nama menu')
                        ->required()
                        ->maxLength(255),
                    Select::make('category_id')
                        ->label('Kategori')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload(),
                    TextInput::make('price')
                        ->label('Harga jual')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp'),
                    TextInput::make('sort_order')
                        ->label('Urutan')
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_available')
                        ->label('Tersedia di kasir')
                        ->default(true),
                ]),
            Section::make('Opsi menu')
                ->description('Pilih grup opsi yang berlaku, misalnya Gula, Es, atau Ukuran.')
                ->schema([
                    Select::make('optionGroups')
                        ->label('Grup opsi')
                        ->relationship('optionGroups', 'name')
                        ->multiple()
                        ->preload(),
                ]),
        ]);
    }
}