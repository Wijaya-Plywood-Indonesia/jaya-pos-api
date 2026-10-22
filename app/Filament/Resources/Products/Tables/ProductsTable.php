<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Hanya terlihat oleh super admin (user tanpa cafe)
                TextColumn::make('cafe.name')
                    ->label('Cafe')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->visible(fn () => ! auth()->user()?->cafe_id),
                TextColumn::make('name')->label('Menu')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Kategori')->sortable()->badge(),
                TextColumn::make('price')
                    ->label('Harga')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),
                TextColumn::make('optionGroups.name')->label('Opsi')->badge(),
                // Ubah Tersedia/Habis langsung dari tabel
                ToggleColumn::make('is_available')->label('Tersedia'),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
            ])
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