<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string|UnitEnum|null $navigationGroup = 'Pengelolaan';

    protected static ?string $navigationLabel = 'Transaksi';

    protected static ?string $modelLabel = 'Transaksi';

    protected static ?string $pluralModelLabel = 'Transaksi';

    protected static ?int $navigationSort = 5;

    // Transaksi hanya dibuat dari aplikasi kasir
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $methods = ['cash' => 'Tunai', 'qris' => 'QRIS', 'card' => 'Debit', 'transfer' => 'Transfer'];
        $statuses = ['paid' => 'Lunas', 'void' => 'Dibatalkan', 'refunded' => 'Refund', 'open' => 'Terbuka'];

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['payments', 'cashier']))
            ->columns([
                TextColumn::make('cafe.name')
                    ->label('Cafe')
                    ->badge()
                    ->color('gray')
                    ->visible(fn () => ! auth()->user()?->cafe_id),
                TextColumn::make('order_number')->label('No. struk')->searchable()->weight('bold'),
                TextColumn::make('ordered_at')->label('Waktu')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('cashier.name')->label('Kasir'),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->formatStateUsing(fn (string $state) => $state === 'take_away' ? 'Take Away' : 'Dine In'),
                TextColumn::make('payment_method')
                    ->label('Bayar')
                    ->state(fn (Order $r) => $r->payments->pluck('method')->map(fn ($m) => $methods[$m] ?? $m)->join(', ')),
                TextColumn::make('total')
                    ->label('Total')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $statuses[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'paid' => 'success',
                        'void' => 'danger',
                        'refunded' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('kitchen_status')
                    ->label('Antrian')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => [
                        'new' => 'Baru',
                        'preparing' => 'Diproses',
                        'ready' => 'Siap diambil',
                        'served' => 'Selesai',
                    ][$state] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'new' => 'warning',
                        'ready' => 'info',
                        'served' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('ordered_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Status')->options($statuses),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
        ];
    }
}