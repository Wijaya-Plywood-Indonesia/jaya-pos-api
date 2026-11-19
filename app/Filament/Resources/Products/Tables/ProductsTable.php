<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
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
                    ->visible(fn() => ! auth()->user()?->cafe_id),
                // Kotak inisial seperti thumbnail di mockup
                // Foto menu; bila belum ada foto, tampil inisial seperti sebelumnya
                SpatieMediaLibraryImageColumn::make('photo')
                    ->label('')
                    ->collection('photo')
                    ->disk('public')
                    ->conversion('thumb'),
                TextColumn::make('initial')
                    ->label('')
                    ->state(fn($record) => $record->hasMedia('photo')
                        ? null
                        : mb_strtoupper(mb_substr($record->name, 0, 1)))
                    ->badge()
                    ->color('success'),
                TextColumn::make('name')->label('Nama')->searchable()->sortable()->weight('bold'),
                TextColumn::make('category.name')->label('Kategori')->sortable(),
                TextColumn::make('price')
                    ->label('Harga')
                    ->sortable()
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),
                TextColumn::make('option_groups_count')
                    ->label('Opsi')
                    ->counts('optionGroups')
                    ->formatStateUsing(fn($state) => $state . ' grup'),
                // Ubah Tersedia/Habis langsung dari tabel
                ToggleColumn::make('is_available')->label('Tersedia'),
            ])
            ->defaultSort('sort_order')
            ->searchPlaceholder('Cari menu...')
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
