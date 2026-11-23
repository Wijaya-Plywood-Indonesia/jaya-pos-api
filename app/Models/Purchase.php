<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCafe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use BelongsToCafe;

    protected $guarded = [];

    protected $casts = ['purchased_at' => 'datetime'];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
