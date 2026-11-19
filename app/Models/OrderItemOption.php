<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemOption extends Model
{
    // Tabel order_item_options tidak punya created_at / updated_at
    public $timestamps = false;

    protected $guarded = [];
}