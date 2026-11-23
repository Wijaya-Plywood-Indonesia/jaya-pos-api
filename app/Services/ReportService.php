<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Laporan penjualan satu cafe. Dipakai bersama oleh halaman Laporan di panel web,
 * ekspor Excel, dan layar Laporan di aplikasi kasir.
 *
 * Waktu di database UTC. "Hari ini", jam, dan tanggal mengikuti config('jaya.report_timezone').
 * Yang dihitung hanya transaksi berstatus paid.
 */
class ReportService
{
    private const METHODS = [
        'cash' => 'Tunai',
        'qris' => 'QRIS',
        'card' => 'Debit',
        'transfer' => 'Transfer',
    ];

    private const DAYS = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

    public static function timezone(): string
    {
        return (string) config('jaya.report_timezone', 'Asia/Jakarta');
    }

    /**
     * Rentang waktu sebuah periode (dalam zona laporan).
     *
     * @return array{start: Carbon, end: Carbon, bucket: string, label: string}
     */
    public function range(string $period, ?string $from = null, ?string $to = null): array
    {
        $tz = self::timezone();
        $now = Carbon::now($tz);

        if ($period === 'custom') {
            try {
                $start = Carbon::parse($from ?: $now->toDateString(), $tz)->startOfDay();
                $end = Carbon::parse($to ?: $now->toDateString(), $tz)->endOfDay();
            } catch (\Throwable) {
                $start = $now->copy()->subDays(6)->startOfDay();
                $end = $now->copy()->endOfDay();
            }

            if ($end->lt($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            // Paling lama satu tahun, supaya halaman tidak berat
            if ($start->diffInDays($end) > 366) {
                $start = $end->copy()->subDays(366)->startOfDay();
            }

            $label = 'Rentang tanggal';
        } else {
            [$start, $end, $label] = match ($period) {
                'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'Hari ini'],
                'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), 'Kemarin'],
                'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay(), 'Bulan ini'],
                'lastmonth' => [
                    $now->copy()->subMonthNoOverflow()->startOfMonth(),
                    $now->copy()->subMonthNoOverflow()->endOfMonth(),
                    'Bulan lalu',
                ],
                default => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay(), '7 hari terakhir'],
            };
        }

        return [
            'start' => $start,
            'end' => $end,
            'bucket' => $start->isSameDay($end) ? 'hour' : 'day',
            'label' => $label,
        ];
    }

    /** Ringkasan satu periode, siap ditampilkan atau dikirim sebagai JSON. */
    public function build(int $cafeId, string $period, ?string $from = null, ?string $to = null): array
    {
        $r = $this->range($period, $from, $to);
        [$s, $e] = $this->utcBounds($r);

        $paid = fn () => DB::table('orders')
            ->where('orders.cafe_id', $cafeId)
            ->where('orders.status', 'paid')
            ->whereBetween('orders.paid_at', [$s, $e]);

        $agg = $paid()->selectRaw(
            'COUNT(*) AS trx, COALESCE(SUM(subtotal),0) AS subtotal, COALESCE(SUM(discount_total),0) AS discount, '
            . 'COALESCE(SUM(service_amount),0) AS service, COALESCE(SUM(tax_amount),0) AS tax, '
            . 'COALESCE(SUM(rounding),0) AS rounding, COALESCE(SUM(total),0) AS total'
        )->first();

        $trx = (int) $agg->trx;
        $gross = (int) $agg->subtotal;
        $discount = (int) $agg->discount;
        $net = $gross - $discount;

        // HPP: bahan yang terpakai oleh transaksi pada periode ini, dari buku besar stok
        $hpp = (int) round((float) DB::table('stock_movements as sm')
            ->join('orders as o', 'o.id', '=', 'sm.reference_id')
            ->where('sm.reference_type', 'order')
            ->where('sm.type', 'sale')
            ->where('o.cafe_id', $cafeId)
            ->where('o.status', 'paid')
            ->whereBetween('o.paid_at', [$s, $e])
            ->sum(DB::raw('-sm.quantity * sm.unit_cost')));

        $expenses = (int) DB::table('purchases')
            ->where('cafe_id', $cafeId)
            ->whereBetween('purchased_at', [$s, $e])
            ->sum('total');

        $byMethod = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('orders.cafe_id', $cafeId)
            ->where('orders.status', 'paid')
            ->whereBetween('orders.paid_at', [$s, $e])
            ->groupBy('payments.method')
            ->selectRaw('payments.method AS method, SUM(payments.amount) AS amount')
            ->pluck('amount', 'method');

        $received = (int) $byMethod->sum();
        $payments = $byMethod
            ->map(fn ($amount, $method) => [
                'method' => $method,
                'label' => self::METHODS[$method] ?? $method,
                'amount' => (int) $amount,
                'pct' => $received > 0 ? (int) round($amount / $received * 100) : 0,
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();

        $top = DB::table('order_items as i')
            ->join('orders as o', 'o.id', '=', 'i.order_id')
            ->where('o.cafe_id', $cafeId)
            ->where('o.status', 'paid')
            ->whereBetween('o.paid_at', [$s, $e])
            ->groupBy('i.product_name')
            ->selectRaw('i.product_name AS name, SUM(i.quantity) AS qty, SUM(i.line_total) AS revenue')
            ->orderByDesc('qty')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'qty' => (int) $row->qty,
                'revenue' => (int) $row->revenue,
            ])
            ->all();

        return [
            'period' => $period,
            'label' => $r['label'],
            'range_text' => $this->rangeText($r),
            'bucket' => $r['bucket'],
            'sales_gross' => $gross,
            'discount' => $discount,
            'sales_net' => $net,
            'hpp' => $hpp,
            'gross_profit' => $net - $hpp,
            'margin' => $net > 0 ? (int) round(($net - $hpp) / $net * 100) : 0,
            'transactions' => $trx,
            'average' => $trx > 0 ? (int) round($net / $trx) : 0,
            'service' => (int) $agg->service,
            'tax' => (int) $agg->tax,
            'rounding' => (int) $agg->rounding,
            'total_received' => (int) $agg->total,
            'expenses' => $expenses,
            'payments' => $payments,
            'series' => $this->series($paid(), $r),
            'top' => $top,
        ];
    }

    /**
     * Omset bersih per jam (periode satu hari) atau per hari.
     *
     * @return array<int, array{label: string, value: int}>
     */
    private function series($query, array $r): array
    {
        $tz = self::timezone();
        $appTz = config('app.timezone');
        $rows = $query->get(['orders.paid_at', 'orders.subtotal', 'orders.discount_total']);

        $buckets = [];

        if ($r['bucket'] === 'hour') {
            for ($h = 0; $h < 24; $h++) {
                $buckets[sprintf('%02d', $h)] = ['label' => sprintf('%02d', $h), 'value' => 0];
            }
        } else {
            $day = $r['start']->copy();
            $long = $r['start']->diffInDays($r['end']) > 7;

            while ($day->lte($r['end'])) {
                $buckets[$day->format('Y-m-d')] = [
                    'label' => $long ? $day->format('j') : self::DAYS[$day->dayOfWeek],
                    'value' => 0,
                ];
                $day->addDay();
            }
        }

        foreach ($rows as $row) {
            $t = Carbon::parse($row->paid_at, $appTz)->setTimezone($tz);
            $key = $r['bucket'] === 'hour' ? $t->format('H') : $t->format('Y-m-d');

            if (isset($buckets[$key])) {
                $buckets[$key]['value'] += (int) $row->subtotal - (int) $row->discount_total;
            }
        }

        $series = array_values($buckets);

        // Periode satu hari: buang jam kosong di awal dan akhir
        if ($r['bucket'] === 'hour') {
            $filled = array_keys(array_filter($series, fn ($p) => $p['value'] > 0));

            return $filled ? array_slice($series, min($filled), max($filled) - min($filled) + 1) : [];
        }

        return $series;
    }

    /** Daftar transaksi pada periode, untuk lembar "Transaksi" di Excel. */
    public function transactions(int $cafeId, string $period, ?string $from = null, ?string $to = null): array
    {
        $r = $this->range($period, $from, $to);
        [$s, $e] = $this->utcBounds($r);
        $tz = self::timezone();
        $appTz = config('app.timezone');

        $orders = DB::table('orders as o')
            ->leftJoin('users as u', 'u.id', '=', 'o.cashier_id')
            ->leftJoin('dining_tables as t', 't.id', '=', 'o.dining_table_id')
            ->where('o.cafe_id', $cafeId)
            ->where('o.status', 'paid')
            ->whereBetween('o.paid_at', [$s, $e])
            ->orderBy('o.paid_at')
            ->get([
                'o.id', 'o.order_number', 'o.paid_at', 'u.name as cashier', 'o.type', 't.name as table_name',
                'o.customer_name', 'o.subtotal', 'o.discount_total', 'o.service_amount', 'o.tax_amount',
                'o.rounding', 'o.total',
            ]);

        $methods = DB::table('payments')
            ->whereIn('order_id', $orders->pluck('id'))
            ->get(['order_id', 'method'])
            ->groupBy('order_id')
            ->map(fn ($rows) => $rows->pluck('method')->map(fn ($m) => self::METHODS[$m] ?? $m)->implode(' + '));

        $types = ['dine_in' => 'Dine In', 'take_away' => 'Take Away', 'delivery' => 'Delivery'];

        return $orders->map(fn ($o) => [
            $o->order_number,
            Carbon::parse($o->paid_at, $appTz)->setTimezone($tz)->format('d/m/Y H:i'),
            $o->cashier ?? '-',
            $types[$o->type] ?? $o->type,
            $o->table_name ?? '',
            $o->customer_name ?? '',
            $methods[$o->id] ?? '',
            (int) $o->subtotal,
            (int) $o->discount_total,
            (int) $o->service_amount,
            (int) $o->tax_amount,
            (int) $o->rounding,
            (int) $o->total,
        ])->all();
    }

    /** File Excel laporan: ringkasan, transaksi, menu terlaris, dan omset per hari atau jam. */
    public function xlsx(int $cafeId, string $cafeName, string $period, ?string $from = null, ?string $to = null): string
    {
        $rep = $this->build($cafeId, $period, $from, $to);

        $summary = [
            ['Keterangan', 'Nilai'],
            ['Cafe', $cafeName],
            ['Periode', $rep['label'] . ' (' . $rep['range_text'] . ')'],
            ['Jumlah transaksi', $rep['transactions']],
            ['Penjualan kotor', $rep['sales_gross']],
            ['Diskon diberikan', $rep['discount']],
            ['Omset bersih', $rep['sales_net']],
            ['HPP bahan terpakai', $rep['hpp']],
            ['Laba kotor', $rep['gross_profit']],
            ['Margin laba kotor (%)', $rep['margin']],
            ['Rata-rata per transaksi', $rep['average']],
            ['Layanan (service) terkumpul', $rep['service']],
            ['Pajak terkumpul (titipan)', $rep['tax']],
            ['Pembulatan', $rep['rounding']],
            ['Total diterima', $rep['total_received']],
            ['Belanja restock stok', $rep['expenses']],
        ];

        foreach ($rep['payments'] as $p) {
            $summary[] = ['Diterima via ' . $p['label'], $p['amount']];
        }

        $transactions = array_merge(
            [['No. struk', 'Waktu', 'Kasir', 'Jenis', 'Meja', 'Nama pelanggan', 'Metode bayar', 'Subtotal', 'Diskon', 'Layanan', 'Pajak', 'Pembulatan', 'Total']],
            $this->transactions($cafeId, $period, $from, $to),
        );

        $top = array_merge(
            [['Menu', 'Jumlah porsi', 'Omset']],
            array_map(fn ($t) => [$t['name'], $t['qty'], $t['revenue']], $rep['top']),
        );

        $series = array_merge(
            [[$rep['bucket'] === 'hour' ? 'Jam' : 'Hari / tanggal', 'Omset bersih']],
            array_map(fn ($p) => [$p['label'], $p['value']], $rep['series']),
        );

        return SimpleXlsx::make([
            'Ringkasan' => $summary,
            'Transaksi' => $transactions,
            'Menu terlaris' => $top,
            'Omset' => $series,
        ]);
    }

    /** Batas waktu dalam UTC (zona database), sebagai teks untuk query. */
    private function utcBounds(array $r): array
    {
        $appTz = config('app.timezone');

        return [
            $r['start']->copy()->setTimezone($appTz)->toDateTimeString(),
            $r['end']->copy()->setTimezone($appTz)->toDateTimeString(),
        ];
    }

    private function rangeText(array $r): string
    {
        return $r['start']->isSameDay($r['end'])
            ? $r['start']->format('d/m/Y')
            : $r['start']->format('d/m/Y') . ' – ' . $r['end']->format('d/m/Y');
    }
}
