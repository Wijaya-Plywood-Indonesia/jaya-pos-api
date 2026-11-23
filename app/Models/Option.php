<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Option extends Model
{
    protected $guarded = [];

    protected $casts = [
        'extra_price' => 'integer',
        'stock_multiplier' => 'float',
        'sweet_multiplier' => 'float',
        'extra_ingredients' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(OptionGroup::class, 'option_group_id');
    }
}