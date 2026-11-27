<?php

return [

    /*
    | Zona waktu untuk laporan ("hari ini", jam, tanggal di Excel).
    | Waktu transaksi di database tetap UTC. Isi REPORT_TIMEZONE di .env bila cafe
    | berada di zona lain: Asia/Jakarta (WIB), Asia/Makassar (WITA), Asia/Jayapura (WIT).
    */
    'report_timezone' => env('REPORT_TIMEZONE', 'Asia/Jakarta'),

    /*
    | Alamat email untuk pertanyaan pengguna dan permintaan hapus data.
    | Tampil di halaman Syarat dan Kebijakan Privasi. Isi JAYA_CONTACT_EMAIL di .env.
    */
    'contact_email' => env('JAYA_CONTACT_EMAIL', 'admin@example.com'),

    /*
    | Nomor WhatsApp untuk pertanyaan calon pelanggan, format internasional tanpa + atau spasi,
    | contoh 6281234567890. Kosongkan bila tidak ingin menampilkan tombol WhatsApp.
    */
    'whatsapp' => env('JAYA_WHATSAPP'),

];
