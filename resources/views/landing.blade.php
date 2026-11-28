@extends('layouts.public')

@section('title', 'Mawra POS · Kasir cafe yang mudah')

@section('head')
    <style>
        .hero { text-align: center; padding: 56px 0 28px; }
        .hero h1 { font-size: clamp(30px, 6vw, 46px); line-height: 1.15; margin: 0 0 14px; letter-spacing: -.5px; }
        .hero p { color: var(--muted); font-size: 18px; max-width: 560px; margin: 0 auto 26px; }
        .hero .btn { margin: 0 6px 10px; }
        .hero small { display: block; color: var(--muted); font-size: 13px; }
        h2.sec { font-size: clamp(22px, 4vw, 28px); text-align: center; margin: 56px 0 8px; letter-spacing: -.3px; }
        p.sec-sub { text-align: center; color: var(--muted); margin: 0 auto 24px; max-width: 540px; }
        .shots { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-top: 34px; }
        .shots img { max-width: 100%; height: auto; border-radius: 16px; border: 1px solid var(--line); box-shadow: 0 10px 30px rgba(0,0,0,.18); }
        .shots img.tablet { width: 560px; }
        .shots img.phone { width: 240px; }
        /* Flex (bukan grid) supaya kartu sisa di baris terakhir berada di tengah */
        .feats { display: flex; flex-wrap: wrap; justify-content: center; gap: 14px; }
        .feat { flex: 0 1 calc(25% - 10.5px); min-width: 220px; background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 18px; }
        .feat b { display: block; margin-bottom: 4px; }
        .feat span { color: var(--muted); font-size: 14px; }
        .steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px; counter-reset: s; }
        .step { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 18px 18px 18px 64px; position: relative; }
        .step::before { counter-increment: s; content: counter(s); position: absolute; left: 18px; top: 18px; width: 32px; height: 32px; border-radius: 50%; background: var(--red); color: #fff; font-weight: 800; display: flex; align-items: center; justify-content: center; }
        .step b { display: block; margin-bottom: 4px; }
        .step span { color: var(--muted); font-size: 14px; }
        .price { max-width: 420px; margin: 0 auto; background: var(--card); border: 1.5px solid var(--red); border-radius: 18px; padding: 24px; text-align: center; }
        .price .big { font-size: 34px; font-weight: 800; margin: 6px 0; }
        .price ul { list-style: none; padding: 0; margin: 14px 0 20px; text-align: left; color: var(--muted); font-size: 14.5px; }
        .price li { padding: 4px 0 4px 24px; position: relative; }
        .price li::before { content: "✓"; position: absolute; left: 0; color: var(--red); font-weight: 800; }
        .faq { max-width: 720px; margin: 0 auto; }
        .faq details { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 14px 18px; margin-bottom: 10px; }
        .faq summary { font-weight: 700; cursor: pointer; }
        .faq p { color: var(--muted); margin: 10px 0 0; font-size: 14.5px; }
        .cta { text-align: center; margin: 56px 0 8px; padding: 34px 20px; background: var(--card); border: 1px solid var(--line); border-radius: 20px; }
        .cta h2 { margin: 0 0 8px; }
        .cta p { color: var(--muted); margin: 0 0 18px; }
        .cta .btn { margin: 0 6px 8px; }
        .btn.wa { border-color: #16a34a; color: #16a34a; background: transparent; }
    </style>
@endsection

@section('content')
    @php
        $wa = config('jaya.whatsapp');
        $waUrl = $wa ? 'https://wa.me/'.preg_replace('/\D/', '', $wa).'?text='.rawurlencode('Halo, saya mau tanya tentang Mawra POS') : null;
        $shots = array_filter([
            'tablet' => file_exists(public_path('images/landing/kasir.png')) ? '/images/landing/kasir.png' : null,
            'phone'  => file_exists(public_path('images/landing/laporan.png')) ? '/images/landing/laporan.png' : null,
        ]);
    @endphp

    <section class="hero">
        <h1>Kasir cafe yang mudah dipakai,<br>jalan juga saat internet putus</h1>
        <p>Pesan, bayar, cetak struk, kelola stok, dan lihat laporan. Daftar gratis, coba jualan dalam hitungan menit.</p>
        <a class="btn primary" href="/daftar">Daftar gratis</a>
        @if ($waUrl)
            <a class="btn wa" href="{{ $waUrl }}" target="_blank" rel="noopener">Tanya lewat WhatsApp</a>
        @else
            <a class="btn ghost" href="/admin">Masuk</a>
        @endif
        <small>Tanpa kartu kredit. Sudah punya akun? <a href="/admin">Masuk</a></small>

        @if ($shots)
            <div class="shots">
                @isset($shots['tablet'])<img class="tablet" src="{{ $shots['tablet'] }}" alt="Tampilan kasir di tablet" loading="lazy">@endisset
                @isset($shots['phone'])<img class="phone" src="{{ $shots['phone'] }}" alt="Tampilan laporan penjualan" loading="lazy">@endisset
            </div>
        @endif
    </section>

    <h2 class="sec">Mulai jualan dalam 3 langkah</h2>
    <p class="sec-sub">Tidak perlu instalasi rumit atau menunggu tim kami.</p>
    <section class="steps">
        <div class="step"><b>Daftar dan pilih jenis usaha</b><span>Pilih Kafe kopi untuk langsung mendapat menu, kategori, opsi, dan resep contoh lengkap dengan foto, atau mulai kosong.</span></div>
        <div class="step"><b>Atur menu, harga, dan kasir</b><span>Ganti menu contoh dengan menu cafe-mu, atur pajak, lalu tambahkan akun kasir dengan PIN masing-masing.</span></div>
        <div class="step"><b>Aktifkan tablet dengan kode</b><span>Buat kode singkat di panel admin, masukkan di aplikasi kasir, lalu mulai menerima pesanan.</span></div>
    </section>

    <h2 class="sec">Semua yang dibutuhkan kasir cafe</h2>
    <p class="sec-sub">Dibuat untuk cafe dan kedai kecil sampai menengah.</p>
    <section class="feats">
        <div class="feat"><b>Tetap jalan tanpa internet</b><span>Transaksi tersimpan di tablet dan terkirim otomatis saat online lagi.</span></div>
        <div class="feat"><b>Stok dan resep otomatis</b><span>Bahan berkurang sendiri setiap ada penjualan, menu habis otomatis berlabel.</span></div>
        <div class="feat"><b>Diskon, promo, dan voucher</b><span>Diskon manual dengan persetujuan PIN owner.</span></div>
        <div class="feat"><b>Laporan dan Excel</b><span>Omset, laba kotor, menu terlaris, dan ekspor siap pakai.</span></div>
        <div class="feat"><b>Printer struk Bluetooth</b><span>Mendukung printer struk 58 mm dan 80 mm.</span></div>
        <div class="feat"><b>Banyak kasir</b><span>Setiap kasir masuk dengan PIN sendiri, tercatat siapa yang melayani.</span></div>
    </section>

    <h2 class="sec">Harga</h2>
    <p class="sec-sub">Gratis selama masa uji. Kami akan memberi kabar sebelum ada perubahan.</p>
    <section class="price">
        <div style="font-weight:700">Paket Gratis</div>
        <div class="big">Rp 0</div>
        <ul>
            <li>Menu, kategori, dan opsi tanpa batas</li>
            <li>Kasir dengan PIN dan laporan penjualan</li>
            <li>Stok, resep, dan struk Bluetooth</li>
            <li>Bekerja saat internet putus</li>
        </ul>
        <a class="btn primary" href="/daftar">Daftar gratis</a>
    </section>

    <h2 class="sec">Pertanyaan yang sering diajukan</h2>
    <section class="faq" style="margin-top:20px">
        <details><summary>Apakah tetap bisa jualan saat internet mati?</summary><p>Bisa. Transaksi disimpan di tablet dan dikirim ke server otomatis saat internet kembali.</p></details>
        <details><summary>Perangkat apa yang dibutuhkan?</summary><p>Tablet atau HP Android. Printer struk Bluetooth 58 mm atau 80 mm bersifat opsional.</p></details>
        <details><summary>Berapa tablet dan kasir yang bisa dipakai?</summary><p>Beberapa tablet bisa dihubungkan dengan kode aktivasi, dan setiap kasir punya PIN sendiri. Setiap tablet bisa dikeluarkan dari panel admin kalau hilang.</p></details>
        <details><summary>Bagaimana dengan data saya?</summary><p>Data setiap cafe terpisah dari cafe lain. Detailnya ada di <a href="/privasi">Kebijakan privasi</a>.</p></details>
        <details><summary>Apakah benar-benar gratis?</summary><p>Saat ini ya, selama masa uji. Kami akan memberi pemberitahuan lebih dulu sebelum ada paket berbayar.</p></details>
    </section>

    <section class="cta">
        <h2>Siap mencoba?</h2>
        <p>Daftar sekarang dan coba transaksi pertamamu hari ini.</p>
        <a class="btn primary" href="/daftar">Daftar gratis</a>
        @if ($waUrl)<a class="btn wa" href="{{ $waUrl }}" target="_blank" rel="noopener">Tanya lewat WhatsApp</a>@endif
    </section>
@endsection
