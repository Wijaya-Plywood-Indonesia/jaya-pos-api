<?php

namespace App\Filament\Resources\DiningTables\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiningTablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cafe.name')
                    ->label('Cafe')
                    ->badge()
                    ->color('gray')
                    ->visible(fn () => ! auth()->user()?->cafe_id),
                TextColumn::make('name')->label('Meja')->searchable()->sortable(),
                TextColumn::make('capacity')->label('Kapasitas')->placeholder('-'),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Belum ada meja')
            ->emptyStateDescription('Cafe tanpa nomor meja? Biarkan kosong, kasir tidak akan melihat pilihan meja.')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
