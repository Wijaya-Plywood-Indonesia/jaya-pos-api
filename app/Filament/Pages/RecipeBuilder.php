<?php

namespace App\Filament\Pages;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

class RecipeBuilder extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $navigationLabel = 'Resep & Bahan';

    protected static ?string $title = 'Resep & Bahan';

    protected static ?string $slug = 'resep-bahan';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.recipe-builder';

    public ?int $productId = null;

    public ?int $ingredientId = null;

    public $quantity = null;

    // Resep dikelola per cafe, jadi hanya user cafe (owner / manager)
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->cafe_id !== null && $user->hasAnyRole(['owner', 'manager']);
    }

    public function getSubheading(): ?string
    {
        return 'Takaran per porsi. Stok berkurang otomatis dan HPP dihitung dari sini.';
    }

    public function mount(): void
    {
        $this->productId = Product::query()->orderBy('sort_order')->value('id');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newIngredient')
                ->label('+ Bahan baru')
                ->color('gray')
                ->schema([
                    TextInput::make('name')->label('Nama bahan')->required()->maxLength(255),
                    Select::make('unit')->label('Satuan')->required()
                        ->options(['g' => 'g', 'ml' => 'ml', 'pcs' => 'pcs', 'porsi' => 'porsi']),
                    TextInput::make('cost_per_unit')->label('Harga per satuan')
                        ->numeric()->minValue(0)->required()->prefix('Rp'),
                    TextInput::make('min_stock')->label('Stok minimum')
                        ->numeric()->minValue(0)->default(0),
                ])
                ->action(function (array $data) {
                    Ingredient::create($data);

                    Notification::make()->title('Bahan ditambahkan')->success()->send();
                }),
        ];
    }

    public function products()
    {
        return Product::query()->orderBy('sort_order')->get(['id', 'name']);
    }

    public function ingredients()
    {
        return Ingredient::query()->orderBy('name')->get(['id', 'name', 'unit']);
    }

    /** Ringkasan menu terpilih: harga jual, HPP, margin, dan baris resep. */
    public function summary(): ?array
    {
        $product = $this->productId ? Product::find($this->productId) : null;

        if (! $product) {
            return null;
        }

        $rows = Recipe::query()
            ->where('product_id', $product->id)
            ->with('ingredient')
            ->get();

        $hpp = $rows->sum(fn ($r) => $r->quantity * ($r->ingredient?->cost_per_unit ?? 0));
        $margin = $product->price > 0 ? ($product->price - $hpp) / $product->price * 100 : 0;

        return compact('product', 'rows', 'hpp', 'margin');
    }

    public function addRow(): void
    {
        $this->validate([
            'productId' => ['required', 'integer'],
            'ingredientId' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'gt:0'],
        ], [], [
            'ingredientId' => 'bahan',
            'quantity' => 'takaran',
        ]);

        // findOrFail lewat global scope cafe: tidak bisa menyentuh data cafe lain
        $product = Product::findOrFail($this->productId);
        $ingredient = Ingredient::findOrFail($this->ingredientId);

        Recipe::updateOrCreate(
            ['product_id' => $product->id, 'ingredient_id' => $ingredient->id],
            ['quantity' => $this->quantity]
        );

        $this->reset('ingredientId', 'quantity');
    }

    public function removeRow(int $recipeId): void
    {
        $product = Product::findOrFail($this->productId);

        Recipe::where('product_id', $product->id)->whereKey($recipeId)->delete();
    }

    public function rp(float|int $n): string
    {
        return ($n < 0 ? '-' : '') . number_format(abs(round($n)), 0, ',', '.');
    }
}
