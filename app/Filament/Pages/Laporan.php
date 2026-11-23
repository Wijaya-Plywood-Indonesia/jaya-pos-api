<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use UnitEnum;

class Laporan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Pengelolaan';

    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $title = 'Laporan';

    protected static ?string $slug = 'laporan';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.laporan';

    public string $period = 'week';

    public ?string $from = null;

    public ?string $to = null;

    // Laporan per cafe, jadi hanya owner atau manajer cafe
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->cafe_id !== null && $user->hasAnyRole(['owner', 'manager']);
    }

    public function getSubheading(): ?string
    {
        return 'Omset, laba kotor, metode bayar, dan menu terlaris dari transaksi yang sudah dibayar.';
    }

    public function mount(): void
    {
        $this->from = now(ReportService::timezone())->subDays(6)->toDateString();
        $this->to = now(ReportService::timezone())->toDateString();
    }

    public function periods(): array
    {
        return [
            'day' => 'Hari ini',
            'yesterday' => 'Kemarin',
            'week' => '7 hari terakhir',
            'month' => 'Bulan ini',
            'lastmonth' => 'Bulan lalu',
            'custom' => 'Pilih tanggal',
        ];
    }

    public function report(): array
    {
        return app(ReportService::class)->build(
            (int) auth()->user()->cafe_id,
            $this->period,
            $this->from,
            $this->to,
        );
    }

    // Unduh Excel (.xlsx): ringkasan, transaksi, menu terlaris, dan omset per hari atau jam
    public function exportExcel()
    {
        $user = auth()->user();
        $cafe = $user->cafe;
        $service = app(ReportService::class);

        $binary = $service->xlsx((int) $user->cafe_id, $cafe?->name ?? 'Cafe', $this->period, $this->from, $this->to);
        $name = 'laporan-' . Str::slug($cafe?->name ?? 'cafe') . '-' . now(ReportService::timezone())->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(
            function () use ($binary) {
                echo $binary;
            },
            $name,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function rp(int|float $n): string
    {
        $n = (int) round($n);

        return ($n < 0 ? '− ' : '') . 'Rp ' . number_format(abs($n), 0, ',', '.');
    }

    // Angka singkat untuk label grafik: 1.250.000 -> 1,3 jt, 45.000 -> 45 rb
    public function short(int $n): string
    {
        if ($n >= 1_000_000) {
            return rtrim(rtrim(number_format($n / 1_000_000, 1, ',', '.'), '0'), ',') . ' jt';
        }

        if ($n >= 1000) {
            return round($n / 1000) . ' rb';
        }

        return (string) $n;
    }
}
