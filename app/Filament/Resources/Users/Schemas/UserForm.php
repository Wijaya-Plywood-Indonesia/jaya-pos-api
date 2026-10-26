<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data pengguna')
                ->columns(2)
                ->schema([
                    // Super admin memilih cafe; user cafe otomatis memakai cafe-nya
                    Select::make('cafe_id')
                        ->label('Cafe')
                        ->relationship('cafe', 'name')
                        ->visible(fn () => ! auth()->user()?->cafe_id)
                        ->columnSpanFull(),
                    Hidden::make('cafe_id')
                        ->default(fn () => auth()->user()?->cafe_id)
                        ->visible(fn () => (bool) auth()->user()?->cafe_id),
                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    TextInput::make('password')
                        ->label('Password (untuk login web)')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn ($state) => filled($state))
                        ->minLength(8),
                    TextInput::make('pin')
                        ->label('PIN kasir (4 angka)')
                        ->password()
                        ->revealable()
                        ->inputMode('numeric')
                        ->maxLength(4)
                        ->regex('/^\d{4}$/')
                        ->validationMessages(['regex' => 'PIN harus 4 angka.'])
                        ->dehydrated(fn ($state) => filled($state))
                        ->helperText('Kosongkan saat edit jika tidak ingin mengganti PIN.'),
                    Select::make('outlet_id')
                        ->label('Outlet')
                        ->relationship('outlet', 'name')
                        ->preload(),
                    Select::make('roles')
                        ->label('Role')
                        ->relationship(
                            'roles',
                            'name',
                            fn (Builder $query) => auth()->user()?->cafe_id
                                ? $query->where('name', '!=', 'super_admin')
                                : $query
                        )
                        ->multiple()
                        ->preload()
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ]),
        ]);
    }
}