@extends('layouts.public')

@section('title', ($type === 'syarat' ? 'Syarat layanan' : 'Kebijakan privasi') . ' · Mawra POS')

@section('head')
    <style>
        .doc { max-width: 720px; margin: 18px auto 0; background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: 28px; }
        .doc h1 { margin: 0 0 6px; font-size: 26px; }
        .doc h2 { font-size: 16px; margin: 24px 0 6px; }
        .doc p, .doc li { font-size: 15px; }
    </style>
@endsection

@section('content')
    @php($email = config('jaya.contact_email'))

    <div class="doc">

        @if ($type === 'syarat')
            <h1>Syarat layanan</h1>
            <p>Dengan mendaftar dan memakai Mawra POS, kamu setuju dengan syarat berikut.</p>

            <h2>1. Layanan</h2>
            <p>Mawra POS adalah aplikasi kasir dan panel web untuk mencatat penjualan, stok, dan laporan cafe. Layanan saat ini diberikan gratis dan fitur atau batasannya dapat berubah. Perubahan besar diberitahukan lebih dulu.</p>

            <h2>2. Akun dan keamanan</h2>
            <p>Kamu bertanggung jawab atas kerahasiaan password dan PIN, serta atas semua tindakan yang dilakukan lewat akun cafe-mu, termasuk oleh karyawan yang kamu beri akses.</p>

            <h2>3. Data milikmu</h2>
            <p>Data menu, transaksi, dan laporan adalah milik cafe-mu. Kamu dapat mengunduhnya (misalnya ekspor Excel) dan meminta penghapusan akun kapan saja lewat {{ $email }}.</p>

            <h2>4. Pemakaian yang tidak diperbolehkan</h2>
            <p>Dilarang memakai layanan untuk tindakan melanggar hukum, mengganggu keamanan atau kinerja sistem, atau mengakses data cafe lain.</p>

            <h2>5. Ketersediaan</h2>
            <p>Kami berusaha menjaga layanan tetap berjalan, tetapi tidak menjamin tanpa gangguan. Aplikasi kasir tetap dapat mencatat transaksi saat offline, dan datanya terkirim saat tersambung kembali. Pembukuan resmi dan pajak tetap menjadi tanggung jawabmu.</p>

            <h2>6. Penghentian</h2>
            <p>Kamu dapat berhenti memakai layanan kapan saja. Akun yang melanggar syarat ini dapat dinonaktifkan.</p>

            <h2>7. Kontak</h2>
            <p>Pertanyaan: {{ $email }}</p>
        @else
            <h1>Kebijakan privasi</h1>
            <p>Kebijakan ini menjelaskan data apa yang kami simpan dan untuk apa.</p>

            <h2>Data yang kami simpan</h2>
            <ul>
                <li>Data akun: nama, email, dan password (disimpan terenkripsi), serta PIN (disimpan ter-hash).</li>
                <li>Data cafe: nama cafe, menu, harga, bahan, resep, pengaturan pajak dan struk.</li>
                <li>Data transaksi: isi pesanan, metode bayar, kasir, waktu, dan nama pelanggan bila diisi kasir.</li>
                <li>Data perangkat: nama perangkat dan waktu terakhir dipakai, untuk keamanan akses.</li>
            </ul>

            <h2>Untuk apa data dipakai</h2>
            <p>Hanya untuk menjalankan layanan: mencatat penjualan, menghitung stok dan laporan, dan menjaga keamanan akun. Kami tidak menjual data cafe-mu.</p>

            <h2>Keamanan</h2>
            <p>Data tiap cafe dipisahkan dari cafe lain. Akses ke aplikasi kasir memakai token per perangkat yang dapat dicabut.</p>

            <h2>Penyimpanan dan penghapusan</h2>
            <p>Data disimpan selama akunmu aktif. Kamu dapat meminta penghapusan akun dan seluruh datanya lewat {{ $email }}.</p>

            <h2>Pihak ketiga</h2>
            <p>Server dijalankan di penyedia hosting yang kami pilih. Bila di masa depan ada layanan lain yang menerima data (misalnya pengiriman email), kebijakan ini akan diperbarui.</p>

            <h2>Kontak</h2>
            <p>{{ $email }}</p>
        @endif
    </div>
@endsection
