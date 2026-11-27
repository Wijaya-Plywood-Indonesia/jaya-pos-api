<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Cafe;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CafeTemplates;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

// Daftar langkah awal untuk cafe baru. Centangnya terisi otomatis dari data, bukan dicentang manual.
class MulaiBerjualan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?string $navigationLabel = 'Mulai berjualan';

    protected static ?string $title = 'Mulai berjualan';

    protected static ?string $slug = 'mulai';

    protected static ?int $navigationSort = -100;

    protected string $view = 'filament.pages.mulai-berjualan';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->cafe_id !== null && $user->hasAnyRole(['owner', 'manager']);
    }

    // Menu ini hilang dari samping setelah semua langkah selesai
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess() && static::remaining() > 0;
    }

    public static function getNavigationBadge(): ?string
    {
        $left = static::canAccess() ? static::remaining() : 0;

        return $left > 0 ? (string) $left : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function getSubheading(): ?string
    {
        return 'Lima langkah supaya cafe-mu siap berjualan.';
    }

    public static function remaining(): int
    {
        return count(array_filter(static::buildSteps(), fn ($s) => ! $s['done']));
    }

    /** @return array<int, array{title: string, hint: string, done: bool, url: ?string, cta: ?string}> */
    public static function buildSteps(): array
    {
        $cafeId = (int) auth()->user()->cafe_id;
        $cafe = Cafe::find($cafeId);

        // "Pernah menyimpan Pengaturan Cafe": cafe diperbarui setelah dibuat
        $settingsDone = $cafe !== null
            && $cafe->updated_at !== null
            && $cafe->updated_at->gt($cafe->created_at->copy()->addMinutes(2));

        $menuDone = Product::withoutGlobalScopes()
            ->where('cafe_id', $cafeId)
            ->where('is_sample', false)
            ->whereNull('deleted_at')
            ->exists();

        $cashierDone = User::where('cafe_id', $cafeId)->role('cashier')->exists();

        // Tablet yang sudah pernah masuk punya token akses
        $tabletDone = DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->whereIn('tokenable_id', User::where('cafe_id', $cafeId)->pluck('id'))
            ->exists();

        $orderDone = Order::withoutGlobalScopes()->where('cafe_id', $cafeId)->exists();

        return [
            [
                'title' => 'Atur pajak dan pesan struk',
                'hint' => 'Isi persentase pajak, layanan, dan pembulatan sesuai cafe-mu. Kalau tidak memakai pajak, cukup simpan sekali.',
                'done' => $settingsDone,
                'url' => CafeSettings::getUrl(),
                'cta' => 'Buka pengaturan',
            ],
            [
                'title' => 'Sesuaikan menu dan harga',
                'hint' => 'Ganti menu contoh dengan menu cafe-mu, atau tambahkan menu baru. Langkah ini selesai setelah ada satu menu buatanmu sendiri.',
                'done' => $menuDone,
                'url' => ProductResource::getUrl('index'),
                'cta' => 'Buka menu',
            ],
            [
                'title' => 'Tambah akun kasir',
                'hint' => 'Setiap kasir punya PIN sendiri, jadi tercatat siapa yang melayani.',
                'done' => $cashierDone,
                'url' => UserResource::getUrl('index'),
                'cta' => 'Buka pengguna',
            ],
            [
                'title' => 'Aktifkan tablet kasir',
                'hint' => 'Di tablet, buka aplikasi lalu isi alamat server dan kode cafe (ada di kotak di bawah). Pilih akunmu dan masukkan PIN.',
                'done' => $tabletDone,
                'url' => null,
                'cta' => null,
            ],
            [
                'title' => 'Coba satu transaksi',
                'hint' => 'Buat pesanan di tablet, bayar, lalu lihat hasilnya di menu Transaksi dan Laporan.',
                'done' => $orderDone,
                'url' => OrderResource::getUrl('index'),
                'cta' => 'Buka transaksi',
            ],
        ];
    }

    public function steps(): array
    {
        return static::buildSteps();
    }

    public function cafeCode(): string
    {
        return (string) Cafe::find((int) auth()->user()->cafe_id)?->slug;
    }

    // Alamat yang diketik di tablet. Di jaringan lokal, ganti localhost dengan IP komputer.
    public function serverUrl(): string
    {
        return request()->getSchemeAndHttpHost();
    }

    public function sampleCount(): int
    {
        return CafeTemplates::sampleCount((int) auth()->user()->cafe_id);
    }

    public function deleteSamples(): void
    {
        CafeTemplates::removeSamples((int) auth()->user()->cafe_id);

        Notification::make()
            ->title('Data contoh dihapus')
            ->body('Menu, kategori, opsi, dan bahan contoh sudah dibersihkan. Data yang kamu buat sendiri tidak berubah.')
            ->success()
            ->send();
    }
}
