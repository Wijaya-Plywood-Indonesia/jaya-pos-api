<?php

return [

    /*
    | Zona waktu untuk laporan ("hari ini", jam, tanggal di Excel).
    | Waktu transaksi di database tetap UTC. Isi REPORT_TIMEZONE di .env bila cafe
    | berada di zona lain: Asia/Jakarta (WIB), Asia/Makassar (WITA), Asia/Jayapura (WIT).
    */
    'report_timezone' => env('REPORT_TIMEZONE', 'Asia/Jakarta'),

];
