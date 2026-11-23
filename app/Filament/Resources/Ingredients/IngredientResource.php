<?php

namespace App\Filament\Resources\Ingredients;

use App\Filament\Resources\Ingredients\Pages\ManageIngredients;
use App\Models\Ingredient;
use App\Services\StockService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class IngredientResource extends Resource
{
    protected static ?string $model = Ingredient::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $navigationLabel = 'Daftar Bahan';

    protected static ?string $modelLabel = 'Bahan';

    protected static ?string $pluralModelLabel = 'Bahan';

    protected static ?int $navigationSort = 4;

    // Bahan milik cafe, jadi tidak ditampilkan untuk super admin
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->cafe_id !== null && $user->hasAnyRole(['owner', 'manager']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama bahan')->required()->maxLength(255),
            Select::make('unit')->label('Satuan')->required()
                ->options(['g' => 'g', 'ml' => 'ml', 'pcs' => 'pcs', 'porsi' => 'porsi']),
            TextInput::make('cost_per_unit')->label('Harga per satuan')
                ->numeric()->minValue(0)->required()->prefix('Rp'),
            TextInput::make('min_stock')->label('Stok minimum')
                ->numeric()->minValue(0)->default(0)
                ->helperText('Di bawah angka ini, bahan ditandai Menipis di aplikasi kasir.'),
            Toggle::make('is_sweetener')->label('Bahan pemanis')
                ->helperText('Aktifkan untuk gula atau sirup. Pemakaiannya ikut berkurang atau hilang saat kasir memilih Less Sugar atau No Sugar.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Bahan')->searchable()->sortable()->weight('bold'),
                TextColumn::make('unit')->label('Satuan'),
                TextColumn::make('cost_per_unit')->label('Harga/satuan')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),
                TextColumn::make('current_stock')->label('Stok')->numeric(decimalPlaces: 1),
                TextColumn::make('min_stock')->label('Minimum')->numeric(decimalPlaces: 1),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Ingredient $record) => $record->current_stock <= 0
                        ? 'Habis'
                        : ($record->current_stock <= $record->min_stock ? 'Menipis' : 'Aman'))
                    ->color(fn (string $state) => match ($state) {
                        'Habis' => 'danger',
                        'Menipis' => 'warning',
                        default => 'success',
                    }),
            ])
            ->defaultSort('name')
            ->recordActions([
                // Menambah stok dan mencatatnya sebagai pembelian (biaya)
                Action::make('restock')
                    ->label('Beli stok')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->modalHeading(fn (Ingredient $record) => 'Beli stok ' . $record->name)
                    ->schema(fn (Ingredient $record) => [
                        TextInput::make('quantity')
                            ->label('Jumlah dibeli')
                            ->numeric()
                            ->required()
                            ->minValue(0.001)
                            ->suffix($record->unit)
                            ->helperText('Biaya dihitung dari harga per satuan bahan ini dan dicatat sebagai pengeluaran.'),
                        TextInput::make('note')->label('Catatan')->maxLength(200),
                    ])
                    ->action(function (Ingredient $record, array $data) {
                        app(StockService::class)->restock(
                            $record,
                            (float) $data['quantity'],
                            auth()->id(),
                            auth()->user()?->outlet_id,
                            $data['note'] ?? null,
                        );

                        Notification::make()->title('Stok ' . $record->name . ' bertambah')->success()->send();
                    }),
                // Menyamakan stok sistem dengan hasil hitung di rak (stok opname)
                Action::make('opname')
                    ->label('Koreksi stok')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('gray')
                    ->modalHeading(fn (Ingredient $record) => 'Koreksi stok ' . $record->name)
                    ->schema(fn (Ingredient $record) => [
                        TextInput::make('actual')
                            ->label('Stok sebenarnya (hasil hitung)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default($record->current_stock)
                            ->suffix($record->unit),
                        TextInput::make('note')
                            ->label('Alasan')
                            ->placeholder('Contoh: stok opname akhir bulan')
                            ->maxLength(200),
                    ])
                    ->action(function (Ingredient $record, array $data) {
                        app(StockService::class)->adjustTo(
                            $record,
                            (float) $data['actual'],
                            auth()->id(),
                            $data['note'] ?? null,
                        );

                        Notification::make()->title('Stok ' . $record->name . ' dikoreksi')->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIngredients::route('/'),
        ];
    }
}
