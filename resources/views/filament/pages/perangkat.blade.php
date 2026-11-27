<x-filament-panels::page>
    <style>
        .pf-card { border: 1px solid rgba(128,128,128,.28); background: rgba(128,128,128,.07); border-radius: 14px; padding: 18px; }
        .pf-card h3 { margin: 0 0 6px; font-size: 15px; font-weight: 800; }
        .pf-sub { font-size: 13px; opacity: .75; margin: 0 0 14px; }
        .pf-btn { font-size: 13.5px; font-weight: 700; padding: 9px 18px; border-radius: 10px; background: #d7263d; color: #fff; border: 0; cursor: pointer; }
        .pf-code { font-family: ui-monospace, monospace; font-size: 38px; font-weight: 800; letter-spacing: 6px; padding: 14px 22px; border-radius: 12px; background: rgba(128,128,128,.15); display: inline-block; }
        .pf-steps { font-size: 13.5px; line-height: 1.7; margin: 14px 0 0; padding-left: 20px; }
        .pf-note { font-size: 12.5px; opacity: .65; margin-top: 10px; }
        .pf-row { display: flex; gap: 12px; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(128,128,128,.2); }
        .pf-row:last-child { border-bottom: 0; }
        .pf-row .who { flex: 1; min-width: 0; font-size: 14px; }
        .pf-row .who small { display: block; opacity: .65; font-size: 12.5px; }
        .pf-out { font-size: 12.5px; font-weight: 700; padding: 6px 12px; border-radius: 9px; border: 1px solid rgba(220,38,38,.5); background: transparent; color: #dc2626; cursor: pointer; }
    </style>

    <div class="pf-card">
        <h3>Tambah tablet</h3>
        <p class="pf-sub">Buat kode, lalu masukkan di aplikasi kasir. Kode berlaku {{ \App\Models\ActivationCode::TTL_MINUTES }} menit dan hanya bisa dipakai satu kali.</p>

        @if ($newCode)
            <div class="pf-code">{{ $newCode }}</div>
            <div class="pf-note">Berlaku sampai pukul {{ $expiresAt }}. Membuat kode baru akan membatalkan kode ini.</div>
            <ol class="pf-steps">
                <li>Di tablet, buka aplikasi kasir.</li>
                <li>Masukkan kode di atas pada layar "Aktifkan perangkat".</li>
                <li>Pilih akunmu dan masukkan PIN.</li>
            </ol>
            <div style="margin-top:14px">
                <button type="button" class="pf-btn" wire:click="generateCode">Buat kode baru</button>
            </div>
        @else
            <button type="button" class="pf-btn" wire:click="generateCode">Buat kode aktivasi</button>
        @endif

        <div class="pf-note">Alamat server yang dipakai aplikasi: <code>{{ $this->serverUrl() }}</code></div>
    </div>

    <div class="pf-card">
        <h3>Perangkat yang sudah masuk</h3>
        <p class="pf-sub">Setiap kali kasir masuk dengan PIN, tablet tercatat di sini. Cabut kalau tablet hilang atau tidak dipakai lagi.</p>

        @forelse ($this->devices() as $d)
            <div class="pf-row" wire:key="dev-{{ $d->id }}">
                <div class="who">
                    <b>{{ $d->device }}</b>
                    <small>
                        Akun {{ $d->user_name }} ·
                        {{ $d->last_used_at ? 'terakhir dipakai ' . \Illuminate\Support\Carbon::parse($d->last_used_at)->diffForHumans() : 'belum pernah dipakai' }}
                    </small>
                </div>
                <button type="button" class="pf-out"
                        wire:click="revoke({{ $d->id }})"
                        wire:confirm="Keluarkan perangkat ini? Tablet akan diminta masuk lagi dengan PIN.">
                    Cabut
                </button>
            </div>
        @empty
            <p class="pf-sub" style="margin:0">Belum ada perangkat yang masuk.</p>
        @endforelse
    </div>
</x-filament-panels::page>
