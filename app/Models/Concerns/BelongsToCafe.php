<?php

namespace App\Models\Concerns;

use App\Models\Cafe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCafe
{
    protected static function bootBelongsToCafe(): void
    {
        // User cafe hanya melihat data cafe-nya sendiri.
        // Super admin (cafe_id kosong) melihat semua.
        static::addGlobalScope('cafe', function (Builder $query) {
            $user = auth()->user();

            if ($user && $user->cafe_id) {
                $query->where($query->getModel()->getTable() . '.cafe_id', $user->cafe_id);
            }
        });

        // Data baru otomatis diberi cafe_id milik user yang login
        static::creating(function ($model) {
            $user = auth()->user();

            if (empty($model->cafe_id) && $user && $user->cafe_id) {
                $model->cafe_id = $user->cafe_id;
            }
        });
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }
}