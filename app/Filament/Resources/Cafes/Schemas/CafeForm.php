<?php

namespace App\Filament\Resources\Cafes\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CafeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data cafe')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nama cafe')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label('Kode cafe (untuk aplikasi kasir)')
                        ->required()
                        ->maxLength(60)
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->unique(ignoreRecord: true)
                        ->helperText('Huruf kecil, angka, dan tanda minus, contoh: kopi-senja. Hati-hati saat mengubah: tablet yang sudah diaktifkan dengan kode lama harus diaktifkan ulang memakai kode baru.'),
                    ColorPicker::make('theme_color')
                        ->label('Warna tema')
                        ->default('#d7263d'),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Nonaktif: owner tidak bisa login web, login aplikasi ditolak, dan token tablet dicabut.'),
                ]),
            Section::make('Pajak dan struk')
                ->columns(2)
                ->schema([
                    TextInput::make('tax_percent')
                        ->label('Pajak / PPN (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(0)
                        ->required(),
                    TextInput::make('service_percent')
                        ->label('Service charge (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(0)
                        ->required(),
                    Select::make('rounding')
                        ->label('Pembulatan')
                        ->options([
                            0 => 'Tanpa pembulatan',
                            100 => 'Ke 100 terdekat',
                            500 => 'Ke 500 terdekat',
                        ])
                        ->default(0)
                        ->required(),
                    TextInput::make('receipt_footer')
                        ->label('Pesan di bawah struk')
                        ->maxLength(255),
                ]),
            // Hanya tampil saat mendaftarkan cafe baru
            Section::make('Akun owner')
                ->description('Akun ini dipakai pemilik cafe untuk login ke panel web dan aplikasi kasir.')
                ->columns(2)
                ->visible(fn (string $operation) => $operation === 'create')
                ->schema([
                    TextInput::make('owner_name')
                        ->label('Nama owner')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('owner_email')
                        ->label('Email owner')
                        ->email()
                        ->required()
                        ->unique('users', 'email'),
                    TextInput::make('owner_password')
                        ->label('Password login web')
                        ->password()
                        ->revealable()
                        ->required()
                        ->minLength(8),
                    TextInput::make('owner_pin')
                        ->label('PIN aplikasi kasir (4-6 angka)')
                        ->password()
                        ->revealable()
                        ->required()
                        ->regex('/^\d{4,6}$/'),
                ]),
        ]);
    }
}