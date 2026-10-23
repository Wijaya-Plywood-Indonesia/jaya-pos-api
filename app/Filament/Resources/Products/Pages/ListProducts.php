<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('+ Menu'),
        ];
    }

    // Chip kategori seperti di mockup: Semua, Kopi, Non-Kopi, ...
    public function getTabs(): array
    {
        $tabs = ['semua' => Tab::make('Semua')];

        $names = Category::query()->orderBy('sort_order')->pluck('name')->unique();

        foreach ($names as $i => $name) {
            $tabs['kategori-' . $i] = Tab::make($name)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas(
                    'category',
                    fn (Builder $q) => $q->where('name', $name)
                ));
        }

        return $tabs;
    }
}
