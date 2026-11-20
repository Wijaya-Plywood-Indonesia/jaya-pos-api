<?php

namespace App\Filament\Resources\Discounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class DiscountsTable
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
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('code')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? 'Voucher ' . $state : 'Promo')
                    ->color(fn (?string $state) => $state ? 'info' : 'success'),
                TextColumn::make('value')
                    ->label('Potongan')
                    ->formatStateUsing(fn ($state, $record) => $record->type === 'percent'
                        ? $state . '%'
                        : 'Rp ' . number_format((int) $state, 0, ',', '.')),
                TextColumn::make('min_purchase')
                    ->label('Min. belanja')
                    ->formatStateUsing(fn ($state) => (int) $state > 0
                        ? 'Rp ' . number_format((int) $state, 0, ',', '.')
                        : '-'),
                TextColumn::make('ends_at')
                    ->label('Berakhir')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Tanpa batas'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Belum ada diskon atau promo')
            ->emptyStateDescription('Buat promo (muncul otomatis di kasir) atau voucher (kasir mengetik kodenya). Diskon manual tidak perlu dibuat di sini.')
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
