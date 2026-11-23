<?php

namespace App\Filament\Resources\OptionGroups\Schemas;

use App\Models\Ingredient;
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
                            Section::make('Pengaruh ke stok bahan')
                                ->description('Opsional. Atur bila pilihan ini mengubah pemakaian bahan, misalnya Large, Less Sugar, atau tambahan extra shot.')
                                ->collapsed()
                                ->columns(2)
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('stock_multiplier')
                                        ->label('Pengali semua bahan resep')
                                        ->numeric()
                                        ->required()
                                        ->default(1)
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->helperText('Large = 1.3 (30% lebih banyak). Normal = 1.'),
                                    TextInput::make('sweet_multiplier')
                                        ->label('Pengali bahan pemanis')
                                        ->numeric()
                                        ->required()
                                        ->default(1)
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->helperText('Less Sugar = 0.5. No Sugar = 0. Normal = 1.'),
                                    Repeater::make('extra_ingredients')
                                        ->label('Bahan tambahan yang ikut terpakai')
                                        ->addActionLabel('+ Bahan')
                                        ->defaultItems(0)
                                        ->columns(2)
                                        ->columnSpanFull()
                                        ->schema([
                                            Select::make('ingredient_id')
                                                ->label('Bahan')
                                                ->options(fn () => Ingredient::query()->orderBy('name')->pluck('name', 'id'))
                                                ->searchable()
                                                ->required(),
                                            TextInput::make('quantity')
                                                ->label('Jumlah per porsi')
                                                ->numeric()
                                                ->minValue(0.001)
                                                ->required(),
                                        ]),
                                ]),
                        ]),
                ]),
        ]);
    }
}