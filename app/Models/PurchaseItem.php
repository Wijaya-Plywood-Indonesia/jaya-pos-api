<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    // Tabel purchase_items tidak punya created_at / updated_at
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'float',
    ];
}
