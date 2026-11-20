<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCafe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use BelongsToCafe;

    protected $guarded = [];

    protected $casts = [
        'value' => 'integer',
        'min_purchase' => 'integer',
        'max_discount' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // Kode voucher selalu huruf besar tanpa spasi. Kosong = promo biasa (bukan voucher).
    public function setCodeAttribute(?string $value): void
    {
        $value = $value === null ? '' : strtoupper(trim($value));

        $this->attributes['code'] = $value === '' ? null : $value;
    }

    // Aktif dan sedang dalam masa berlaku
    public function scopeActiveNow(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
