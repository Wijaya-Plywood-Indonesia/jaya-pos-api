<?php

namespace App\Filament\Pages;

use App\Models\ActivationCode;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use UnitEnum;

// Tambah tablet dengan kode sekali pakai, dan lihat/cabut tablet yang sudah masuk.
class Perangkat extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-tablet';

    protected static string|UnitEnum|null $navigationGroup = 'Pengelolaan';

    protected static ?string $navigationLabel = 'Perangkat';

    protected static ?string $title = 'Perangkat';

    protected static ?int $navigationSort = 25;

    protected static ?string $slug = 'perangkat';

    protected string $view = 'filament.pages.perangkat';

    // Kode yang baru dibuat. Hanya ada di halaman ini, tidak disimpan dalam bentuk asli.
    public ?string $newCode = null;

    public ?string $expiresAt = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->cafe_id !== null && $user->hasAnyRole(['owner', 'manager']);
    }

    public function getSubheading(): ?string
    {
        return 'Hubungkan tablet kasir dengan kode, tanpa mengetik alamat server.';
    }

    public function generateCode(): void
    {
        $this->newCode = ActivationCode::issue((int) auth()->user()->cafe_id, auth()->id());
        $this->expiresAt = now()->addMinutes(ActivationCode::TTL_MINUTES)->format('H:i');
    }

    public function serverUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    public function devices()
    {
        $userIds = User::where('cafe_id', auth()->user()->cafe_id)->pluck('id');

        return DB::table('personal_access_tokens as t')
            ->join('users as u', 'u.id', '=', 't.tokenable_id')
            ->where('t.tokenable_type', User::class)
            ->whereIn('t.tokenable_id', $userIds)
            ->orderByDesc('t.last_used_at')
            ->orderByDesc('t.created_at')
            ->get(['t.id', 't.name as device', 't.last_used_at', 't.created_at', 'u.name as user_name']);
    }

    public function revoke(int $tokenId): void
    {
        $userIds = User::where('cafe_id', auth()->user()->cafe_id)->pluck('id');

        // Hanya token milik pengguna cafe ini
        $deleted = DB::table('personal_access_tokens')
            ->where('id', $tokenId)
            ->where('tokenable_type', User::class)
            ->whereIn('tokenable_id', $userIds)
            ->delete();

        if ($deleted) {
            Notification::make()
                ->title('Perangkat dikeluarkan')
                ->body('Tablet itu akan diminta masuk lagi dengan PIN.')
                ->success()
                ->send();
        }
    }
}
