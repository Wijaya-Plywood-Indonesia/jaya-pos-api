<x-filament-panels::page>
    @php
        $steps = $this->steps();
        $left = count(array_filter($steps, fn ($s) => ! $s['done']));
        $samples = $this->sampleCount();
        $cafeCode = $this->cafeCode();
        $serverUrl = $this->serverUrl();
    @endphp

    <style>
        .mb-card { border: 1px solid rgba(128,128,128,.28); background: rgba(128,128,128,.07); border-radius: 14px; padding: 18px; }
        .mb-card h3 { margin: 0 0 12px; font-size: 15px; font-weight: 800; }
        .mb-ok { border: 1px solid rgba(22,163,74,.45); background: rgba(22,163,74,.1); color: #16a34a; border-radius: 14px; padding: 14px 18px; font-weight: 700; }
        .mb-step { display: flex; gap: 14px; align-items: flex-start; padding: 12px 0; border-bottom: 1px solid rgba(128,128,128,.2); }
        .mb-step:last-child { border-bottom: 0; }
        .mb-num { flex-shrink: 0; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; background: rgba(215,38,61,.15); color: #d7263d; }
        .mb-step.done .mb-num { background: #16a34a; color: #fff; }
        .mb-step.done .mb-body { opacity: .6; }
        .mb-body { flex: 1; min-width: 0; }
        .mb-body b { display: block; font-size: 14.5px; }
        .mb-body span { font-size: 13px; opacity: .75; }
        .mb-go { flex-shrink: 0; align-self: center; font-size: 13px; font-weight: 700; padding: 7px 14px; border-radius: 10px; background: #d7263d; color: #fff; text-decoration: none; }
        .mb-kv { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 8px 0; }
        .mb-kv small { width: 110px; font-size: 12.5px; opacity: .7; font-weight: 600; }
        .mb-kv code { flex: 1; min-width: 160px; font-size: 15px; font-weight: 700; padding: 8px 12px; border-radius: 10px; background: rgba(128,128,128,.15); word-break: break-all; }
        .mb-copy { font-size: 12.5px; font-weight: 700; padding: 6px 12px; border-radius: 9px; border: 1px solid rgba(128,128,128,.4); background: transparent; color: inherit; cursor: pointer; }
        .mb-note { font-size: 12.5px; opacity: .65; margin-top: 8px; }
        .mb-del { font-size: 13px; font-weight: 700; padding: 8px 16px; border-radius: 10px; border: 1px solid rgba(220,38,38,.5); background: transparent; color: #dc2626; cursor: pointer; }
    </style>

    @if ($left === 0)
        <div class="mb-ok">Semua langkah selesai. Cafe-mu siap berjualan.</div>
    @endif

    <div class="mb-card">
        <h3>Langkah awal · {{ 5 - $left }} dari 5 selesai</h3>

        @foreach ($steps as $i => $s)
            <div class="mb-step {{ $s['done'] ? 'done' : '' }}">
                <span class="mb-num">{{ $s['done'] ? '✓' : $i + 1 }}</span>
                <div class="mb-body">
                    <b>{{ $s['title'] }}</b>
                    <span>{{ $s['hint'] }}</span>
                </div>
                @if ($s['url'] && ! $s['done'])
                    <a class="mb-go" href="{{ $s['url'] }}">{{ $s['cta'] }}</a>
                @endif
            </div>
        @endforeach
    </div>

    <div class="mb-card">
        <h3>Untuk mengaktifkan tablet</h3>

        <div class="mb-kv">
            <small>Alamat server</small>
            <code>{{ $serverUrl }}</code>
            <button type="button" class="mb-copy" onclick="navigator.clipboard.writeText(@js($serverUrl))">Salin</button>
        </div>
        <div class="mb-kv">
            <small>Kode cafe</small>
            <code>{{ $cafeCode }}</code>
            <button type="button" class="mb-copy" onclick="navigator.clipboard.writeText(@js($cafeCode))">Salin</button>
        </div>

        <div class="mb-note">Kalau tablet dan komputer ini satu Wi-Fi, ganti "localhost" di alamat server dengan alamat IP komputer (misalnya http://192.168.1.10:8000).</div>
    </div>

    @if ($samples > 0)
        <div class="mb-card">
            <h3>Data contoh</h3>
            <p style="font-size:13.5px; opacity:.8; margin:0 0 12px">
                Ada {{ $samples }} data contoh dari template (menu, kategori, opsi, dan bahan). Berguna untuk mencoba, tapi bisa dihapus sekali klik kalau kamu mau mulai bersih. Data yang kamu buat sendiri tidak ikut terhapus.
            </p>
            <button type="button" class="mb-del"
                    wire:click="deleteSamples"
                    wire:confirm="Hapus semua menu, kategori, opsi, dan bahan contoh? Data yang kamu buat sendiri tidak ikut terhapus.">
                Hapus semua data contoh
            </button>
        </div>
    @endif
</x-filament-panels::page>
