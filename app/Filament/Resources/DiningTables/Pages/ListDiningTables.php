<?php

namespace App\Filament\Resources\DiningTables\Pages;

use App\Filament\Resources\DiningTables\DiningTableResource;
use App\Models\Cafe;
use App\Models\DiningTable;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDiningTables extends ListRecords
{
    protected static string $resource = DiningTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Cafe dengan banyak meja tidak perlu menambah satu per satu
            Action::make('bulk')
                ->label('Tambah banyak meja')
                ->icon('heroicon-o-squares-plus')
                ->color('gray')
                ->modalHeading('Tambah banyak meja sekaligus')
                ->modalDescription('Contoh: awalan "Meja", dari 1 sampai 10 akan membuat Meja 1 sampai Meja 10. Meja yang sudah ada dilewati.')
                ->schema([
                    // Hanya tampil untuk super admin (Anda), karena user cafe otomatis terisi
                    Select::make('cafe_id')
                        ->label('Cafe')
                        ->options(fn () => Cafe::query()->pluck('name', 'id'))
                        ->required()
                        ->visible(fn () => ! auth()->user()?->cafe_id),
                    TextInput::make('prefix')
                        ->label('Awalan nama')
                        ->default('Meja')
                        ->required()
                        ->maxLength(50),
                    TextInput::make('from')
                        ->label('Dari nomor')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required(),
                    TextInput::make('to')
                        ->label('Sampai nomor')
                        ->numeric()
                        ->default(10)
                        ->minValue(1)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $cafeId = auth()->user()?->cafe_id ?: ($data['cafe_id'] ?? null);
                    $from = (int) $data['from'];
                    $to = (int) $data['to'];

                    if (! $cafeId || $to < $from) {
                        Notification::make()
                            ->title('Rentang nomor tidak valid')
                            ->body('Nomor akhir harus sama atau lebih besar dari nomor awal.')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Paling banyak 100 meja per sekali tambah
                    $to = min($to, $from + 99);
                    $prefix = trim($data['prefix']);
                    $made = 0;

                    for ($n = $from; $n <= $to; $n++) {
                        $row = DiningTable::withoutGlobalScopes()->firstOrCreate([
                            'cafe_id' => $cafeId,
                            'name' => $prefix . ' ' . $n,
                        ]);

                        if ($row->wasRecentlyCreated) {
                            $made++;
                        }
                    }

                    Notification::make()
                        ->title($made . ' meja ditambahkan')
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
