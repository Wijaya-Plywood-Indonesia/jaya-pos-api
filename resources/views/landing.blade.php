@extends('layouts.public')

@section('title', 'Jaya POS · Kasir cafe yang mudah')

@section('head')
    <style>
        .hero { text-align: center; padding: 56px 0 28px; }
        .hero h1 { font-size: clamp(30px, 6vw, 46px); line-height: 1.15; margin: 0 0 14px; letter-spacing: -.5px; }
        .hero p { color: var(--muted); font-size: 18px; max-width: 560px; margin: 0 auto 26px; }
        .hero .btn { margin: 0 6px 10px; }
        .feats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-top: 30px; }
        .feat { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 18px; }
        .feat b { display: block; margin-bottom: 4px; }
        .feat span { color: var(--muted); font-size: 14px; }
    </style>
@endsection

@section('content')
    <section class="hero">
        <h1>Kasir cafe yang mudah dipakai,<br>jalan juga saat internet putus</h1>
        <p>Pesan, bayar, cetak struk, kelola stok, dan lihat laporan. Daftar gratis, coba jualan dalam hitungan menit.</p>
        <a class="btn primary" href="/daftar">Daftar gratis</a>
        <a class="btn ghost" href="/admin">Masuk</a>
    </section>

    <section class="feats">
        <div class="feat"><b>Tetap jalan tanpa internet</b><span>Transaksi tersimpan di tablet dan terkirim otomatis saat online lagi.</span></div>
        <div class="feat"><b>Stok dan resep otomatis</b><span>Bahan berkurang sendiri setiap ada penjualan, menu habis otomatis berlabel.</span></div>
        <div class="feat"><b>Diskon, promo, dan voucher</b><span>Diskon manual dengan persetujuan PIN owner.</span></div>
        <div class="feat"><b>Laporan dan Excel</b><span>Omset, laba kotor, menu terlaris, dan ekspor siap pakai.</span></div>
        <div class="feat"><b>Printer struk Bluetooth</b><span>Mendukung printer struk 58 mm dan 80 mm.</span></div>
        <div class="feat"><b>Banyak kasir</b><span>Setiap kasir masuk dengan PIN sendiri, tercatat siapa yang melayani.</span></div>
    </section>
@endsection
