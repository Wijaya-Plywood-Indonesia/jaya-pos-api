<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCafe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToCafe;

    // ID dibuat di perangkat kasir (UUID)
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'ordered_at' => 'datetime',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
        'synced_at' => 'datetime',
        'subtotal' => 'integer',
        'discount_total' => 'integer',
        'service_amount' => 'integer',
        'tax_amount' => 'integer',
        'rounding' => 'integer',
        'total' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}