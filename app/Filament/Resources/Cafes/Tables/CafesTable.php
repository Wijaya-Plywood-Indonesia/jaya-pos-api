<?php

namespace App\Filament\Resources\Cafes\Tables;

use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CafesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Cafe')->searchable()->sortable(),
                TextColumn::make('slug')->label('Kode cafe')->copyable()->searchable(),
                TextColumn::make('users_count')->label('User')->counts('users'),
                TextColumn::make('outlets_count')->label('Outlet')->counts('outlets'),
                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->afterStateUpdated(function ($state) {
                        Notification::make()
                            ->title($state
                                ? 'Cafe diaktifkan kembali. Staf perlu login ulang di tablet.'
                                : 'Cafe dinonaktifkan. Login web dan aplikasi diblokir, token tablet dicabut.')
                            ->success()
                            ->send();
                    }),
                TextColumn::make('created_at')->label('Terdaftar')->date('d M Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}