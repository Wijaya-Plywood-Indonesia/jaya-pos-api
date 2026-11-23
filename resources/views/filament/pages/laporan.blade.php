<x-filament-panels::page>
    @php($r = $this->report())

    <style>
        .jr-bar { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
        .jr-bar .jr-field { min-width: 190px; }
        .jr-bar .jr-fill { flex: 1; }
        .jr-sub { font-size: 13px; opacity: .7; margin: 4px 0 2px; }
        .jr-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; }
        .jr-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 12px; align-items: start; }
        .jr-card { border: 1px solid rgba(128,128,128,.28); background: rgba(128,128,128,.07); border-radius: 14px; padding: 16px; }
        .jr-card h3 { margin: 0 0 10px; font-size: 14px; font-weight: 800; }
        .jr-kpi small { font-size: 12px; opacity: .7; font-weight: 600; }
        .jr-kpi b { display: block; font-size: 22px; font-weight: 800; margin: 4px 0 2px; font-variant-numeric: tabular-nums; }
        .jr-kpi i { font-style: normal; font-size: 12px; opacity: .65; }
        .jr-row { display: flex; justify-content: space-between; gap: 12px; padding: 5px 0; font-size: 14px; font-variant-numeric: tabular-nums; }
        .jr-row.t { border-top: 1px dashed rgba(128,128,128,.5); margin-top: 6px; padding-top: 10px; font-weight: 800; }
        .jr-sec { font-size: 12px; font-weight: 700; opacity: .65; margin: 12px 0 2px; }
        .jr-green { color: #16a34a; }
        .jr-red { color: #dc2626; }
        .jr-amber { color: #d97706; }
        .jr-bar-pm { display: flex; height: 12px; border-radius: 99px; overflow: hidden; background: rgba(128,128,128,.25); margin: 4px 0 12px; }
        .jr-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 7px; }
        .jr-rank { display: flex; align-items: center; gap: 10px; padding: 7px 0; border-bottom: 1px solid rgba(128,128,128,.2); font-size: 14px; }
        .jr-rank:last-child { border-bottom: 0; }
        .jr-rank .n { width: 24px; height: 24px; border-radius: 50%; background: rgba(220,38,38,.15); color: #dc2626; font-weight: 800; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .jr-rank .x { flex: 1; min-width: 0; font-weight: 600; }
        .jr-rank .y { text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; }
        .jr-rank .y small { display: block; font-weight: 500; opacity: .65; }
        .jr-empty { opacity: .6; font-size: 14px; padding: 18px 0; text-align: center; }
        @media print {
            .fi-sidebar, .fi-topbar, .no-print { display: none !important; }
            .jr-card { break-inside: avoid; }
        }
    </style>

    <div class="jr-bar no-print">
        <div class="jr-field">
            <label style="display:block; margin-bottom:6px; font-weight:600; font-size:13px">Periode</label>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="period">
                    @foreach ($this->periods() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        @if ($period === 'custom')
            <div class="jr-field" style="min-width:160px">
                <label style="display:block; margin-bottom:6px; font-weight:600; font-size:13px">Dari</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="from" />
                </x-filament::input.wrapper>
            </div>
            <div class="jr-field" style="min-width:160px">
                <label style="display:block; margin-bottom:6px; font-weight:600; font-size:13px">Sampai</label>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="to" />
                </x-filament::input.wrapper>
            </div>
        @endif

        <div class="jr-fill"></div>

        <x-filament::button color="gray" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel">
            Unduh Excel
        </x-filament::button>
        <x-filament::button color="gray" type="button" onclick="window.print()">
            Cetak / simpan PDF
        </x-filament::button>
    </div>

    <div class="jr-sub">{{ $r['label'] }} · {{ $r['range_text'] }}</div>

    <div class="jr-kpis">
        <div class="jr-card jr-kpi">
            <small>Omset bersih</small>
            <b>{{ $this->rp($r['sales_net']) }}</b>
            <i>Setelah diskon, sebelum pajak</i>
        </div>
        <div class="jr-card jr-kpi">
            <small>Laba kotor</small>
            <b class="jr-green">{{ $this->rp($r['gross_profit']) }}</b>
            <i>Margin {{ $r['margin'] }}%</i>
        </div>
        <div class="jr-card jr-kpi">
            <small>Transaksi</small>
            <b>{{ number_format($r['transactions'], 0, ',', '.') }}</b>
            <i>Yang sudah dibayar</i>
        </div>
        <div class="jr-card jr-kpi">
            <small>Rata-rata per transaksi</small>
            <b>{{ $this->rp($r['average']) }}</b>
            <i>Sebelum pajak</i>
        </div>
    </div>

    <div class="jr-grid">
        <div class="jr-card">
            <h3>Laba rugi · {{ $r['label'] }}</h3>
            <div class="jr-row"><span>Penjualan kotor</span><span>{{ $this->rp($r['sales_gross']) }}</span></div>
            <div class="jr-row"><span>Diskon diberikan</span><span class="jr-amber">− {{ $this->rp($r['discount']) }}</span></div>
            <div class="jr-row"><span>Omset bersih</span><span>{{ $this->rp($r['sales_net']) }}</span></div>
            <div class="jr-row"><span>HPP bahan terpakai</span><span class="jr-red">− {{ $this->rp($r['hpp']) }}</span></div>
            <div class="jr-row t"><span>Laba kotor</span><span class="jr-green">{{ $this->rp($r['gross_profit']) }}</span></div>

            <div class="jr-sec">Arus kas dan titipan, dicatat terpisah</div>
            <div class="jr-row"><span>Belanja restock stok</span><span class="jr-amber">− {{ $this->rp($r['expenses']) }}</span></div>
            <div class="jr-row"><span>Layanan (service) terkumpul</span><span>{{ $this->rp($r['service']) }}</span></div>
            <div class="jr-row"><span>Pajak terkumpul (titipan)</span><span>{{ $this->rp($r['tax']) }}</span></div>
            <div class="jr-row"><span>Pembulatan</span><span>{{ $this->rp($r['rounding']) }}</span></div>
            <div class="jr-sec" style="margin-top:10px">HPP hanya menghitung menu yang sudah punya resep.</div>
        </div>

        <div class="jr-card">
            <h3>Uang diterima per metode bayar</h3>
            @php
                $colors = ['cash' => '#dc2626', 'qris' => '#2563eb', 'card' => '#6b7280', 'transfer' => '#16a34a'];
                $received = max(1, array_sum(array_column($r['payments'], 'amount')));
            @endphp
            @if (count($r['payments']) === 0)
                <div class="jr-empty">Belum ada pembayaran pada periode ini.</div>
            @else
                <div class="jr-bar-pm">
                    @foreach ($r['payments'] as $p)
                        <i style="width: {{ $p['amount'] / $received * 100 }}%; background: {{ $colors[$p['method']] ?? '#9ca3af' }}"></i>
                    @endforeach
                </div>
                @foreach ($r['payments'] as $p)
                    <div class="jr-row">
                        <span><span class="jr-dot" style="background: {{ $colors[$p['method']] ?? '#9ca3af' }}"></span>{{ $p['label'] }}</span>
                        <span>{{ $this->rp($p['amount']) }} · {{ $p['pct'] }}%</span>
                    </div>
                @endforeach
                <div class="jr-row t"><span>Total diterima</span><span>{{ $this->rp($r['total_received']) }}</span></div>
                <div class="jr-sec" style="margin-top:10px">Termasuk pajak, layanan, dan pembulatan.</div>
            @endif
        </div>

        <div class="jr-card">
            <h3>Omset bersih {{ $r['bucket'] === 'hour' ? 'per jam' : 'per hari' }}</h3>
            @php
                $series = $r['series'];
                $n = count($series);
                $max = max(array_merge([1], array_column($series, 'value')));
                $W = 520; $H = 190; $pad = 10; $labelEvery = max(1, (int) ceil($n / 12));
                $slot = $n > 0 ? ($W - $pad * 2) / $n : 0;
                $bw = $slot * 0.62;
            @endphp
            @if ($n === 0 || array_sum(array_column($series, 'value')) === 0)
                <div class="jr-empty">Belum ada penjualan pada periode ini.</div>
            @else
                <svg viewBox="0 0 {{ $W }} {{ $H }}" width="100%" role="img" aria-label="Grafik omset bersih" style="overflow:visible">
                    @foreach ($series as $i => $p)
                        @php
                            $h = ($p['value'] / $max) * ($H - 50);
                            $x = $pad + $i * $slot + ($slot - $bw) / 2;
                            $y = $H - 24 - $h;
                        @endphp
                        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $bw }}" height="{{ max($h, $p['value'] > 0 ? 2 : 0) }}" rx="4" fill="#dc2626" opacity="{{ $p['value'] === $max ? 1 : .55 }}"></rect>
                        @if ($p['value'] > 0 && $n <= 14)
                            <text x="{{ $x + $bw / 2 }}" y="{{ $y - 5 }}" text-anchor="middle" font-size="10" fill="currentColor" opacity=".7">{{ $this->short($p['value']) }}</text>
                        @endif
                        @if ($i % $labelEvery === 0)
                            <text x="{{ $x + $bw / 2 }}" y="{{ $H - 7 }}" text-anchor="middle" font-size="10.5" fill="currentColor" opacity=".7">{{ $p['label'] }}</text>
                        @endif
                    @endforeach
                </svg>
            @endif
        </div>

        <div class="jr-card">
            <h3>Menu terlaris</h3>
            @forelse ($r['top'] as $i => $t)
                <div class="jr-rank">
                    <span class="n">{{ $i + 1 }}</span>
                    <span class="x">{{ $t['name'] }}</span>
                    <span class="y">{{ number_format($t['qty'], 0, ',', '.') }} porsi<small>{{ $this->rp($t['revenue']) }}</small></span>
                </div>
            @empty
                <div class="jr-empty">Belum ada penjualan pada periode ini.</div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
