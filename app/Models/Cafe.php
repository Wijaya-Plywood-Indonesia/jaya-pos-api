<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cafe extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tax_percent' => 'decimal:2',
        'service_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}