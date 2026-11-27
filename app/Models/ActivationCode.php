<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kode aktivasi tablet: sekali pakai, berlaku singkat, hanya hash-nya yang disimpan.
 */
class ActivationCode extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public const TTL_MINUTES = 15;

    // Tanpa huruf/angka yang mudah tertukar (0/O, 1/I/L)
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const LENGTH = 8;

    /** Buat kode baru untuk cafe. Kode lama yang belum terpakai dibatalkan. Mengembalikan kode terformat. */
    public static function issue(int $cafeId, ?int $userId): string
    {
        static::where('cafe_id', $cafeId)->whereNull('used_at')->delete();
        static::where('created_at', '<', now()->subDay())->delete();

        $raw = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $raw .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        static::create([
            'cafe_id' => $cafeId,
            'code_hash' => static::hash($raw),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'created_by' => $userId,
        ]);

        return substr($raw, 0, 4).'-'.substr($raw, 4);
    }

    /** Samakan input pengguna: huruf besar, tanpa spasi atau tanda hubung. */
    public static function normalize(string $input): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input) ?? '');
    }

    public static function hash(string $raw): string
    {
        return hash_hmac('sha256', static::normalize($raw), (string) config('app.key'));
    }
}
