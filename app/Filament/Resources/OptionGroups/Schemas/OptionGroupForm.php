<?php

namespace App\Filament\Resources\OptionGroups\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OptionGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Grup opsi')
                ->columns(2)
                ->schema([
                    Select::make('cafe_id')
                        ->label('Cafe')
                        ->relationship('cafe', 'name')
                        ->required()
                        ->visible(fn () => ! auth()->user()?->cafe_id)
                        ->columnSpanFull(),
                    TextInput::make('name')
                        ->label('Nama grup (contoh: Gula, Ukuran)')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Toggle::make('is_multiple')
                        ->label('Boleh pilih lebih dari satu')
                        ->default(false),
                    Toggle::make('is_required')
                        ->label('Wajib dipilih')
                        ->default(false),
                ]),
            Section::make('Pilihan')
                ->schema([
                    Repeater::make('options')
                        ->label('Daftar pilihan')
                        ->relationship()
                        ->orderColumn('sort_order')
                        ->defaultItems(1)
                        ->addActionLabel('+ Pilihan')
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Nama pilihan')
                                ->required(),
                            TextInput::make('extra_price')
                                ->label('Tambahan harga')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->prefix('Rp'),
                        ]),
                ]),
        ]);
    }
}