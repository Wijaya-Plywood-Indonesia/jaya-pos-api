<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\PersonalAccessToken;

class Cafe extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tax_percent' => 'decimal:2',
        'service_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Cafe dinonaktifkan dari mana pun (tabel, form edit, Tinker): cabut semua token tablet
        static::updated(function (Cafe $cafe) {
            if ($cafe->wasChanged('is_active') && ! $cafe->is_active) {
                $userIds = User::where('cafe_id', $cafe->id)->pluck('id');

                PersonalAccessToken::where('tokenable_type', User::class)
                    ->whereIn('tokenable_id', $userIds)
                    ->delete();
            }
        });
    }

    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}