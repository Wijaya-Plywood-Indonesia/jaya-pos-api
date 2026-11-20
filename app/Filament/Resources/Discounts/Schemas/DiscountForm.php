<?php

namespace App\Filament\Resources\Discounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // Hanya tampil untuk super admin, karena user cafe otomatis terisi
            Select::make('cafe_id')
                ->label('Cafe')
                ->relationship('cafe', 'name')
                ->required()
                ->visible(fn () => ! auth()->user()?->cafe_id),

            TextInput::make('name')
                ->label('Nama promo')
                ->placeholder('Contoh: Happy Hour 15%')
                ->required()
                ->maxLength(100)
                ->helperText('Nama ini tampil di layar kasir dan di struk.'),

            TextInput::make('code')
                ->label('Kode voucher')
                ->placeholder('Contoh: JAYA20')
                ->maxLength(30)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn ($rule) => $rule->where('cafe_id', auth()->user()?->cafe_id),
                )
                ->helperText('Kosongkan bila ini promo biasa: promo langsung muncul sebagai pilihan di kasir. Bila diisi, ini voucher: kasir harus mengetik kodenya.'),

            Select::make('type')
                ->label('Jenis potongan')
                ->options([
                    'percent' => 'Persen (%)',
                    'amount' => 'Potongan Rupiah (Rp)',
                ])
                ->default('percent')
                ->required()
                ->live(),

            TextInput::make('value')
                ->label('Nilai potongan')
                ->numeric()
                ->required()
                ->minValue(1)
                ->maxValue(fn ($get) => $get('type') === 'percent' ? 100 : 100000000)
                ->suffix(fn ($get) => $get('type') === 'percent' ? '%' : null)
                ->prefix(fn ($get) => $get('type') === 'percent' ? null : 'Rp'),

            TextInput::make('min_purchase')
                ->label('Minimal belanja')
                ->numeric()
                ->prefix('Rp')
                ->default(0)
                ->minValue(0)
                ->helperText('Isi 0 bila tanpa minimal belanja.'),

            TextInput::make('max_discount')
                ->label('Potongan maksimal')
                ->numeric()
                ->prefix('Rp')
                ->minValue(1)
                ->visible(fn ($get) => $get('type') === 'percent')
                ->helperText('Contoh: diskon 20% tapi paling banyak Rp 15.000. Kosongkan bila tanpa batas.'),

            DateTimePicker::make('starts_at')
                ->label('Mulai berlaku')
                ->seconds(false)
                ->helperText('Kosongkan bila langsung berlaku.'),

            DateTimePicker::make('ends_at')
                ->label('Berakhir')
                ->seconds(false)
                ->helperText('Kosongkan bila berlaku terus.'),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
        ]);
    }
}
