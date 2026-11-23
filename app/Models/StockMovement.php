<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCafe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Buku besar stok: quantity positif = stok masuk, negatif = stok keluar
class StockMovement extends Model
{
    use BelongsToCafe;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'float',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class)->withTrashed();
    }
}
