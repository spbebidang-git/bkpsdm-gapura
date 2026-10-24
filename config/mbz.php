<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Folder file .mbz contoh (pintasan testing)
    |--------------------------------------------------------------------------
    |
    | Dipakai oleh wizard impor Moodle untuk menampilkan tombol pintasan
    | "pakai file contoh di server" tanpa perlu mengunggah file besar.
    | Struktur yang diharapkan:
    |
    |   <sample_dir>/*.mbz                 → paket full (1 file = semua modul)
    |   <sample_dir>/Versi Per Modul/*.mbz → paket parsial per modul
    |
    | Pintasan hanya muncul saat APP_ENV=local atau APP_DEBUG=true.
    |
    */

    'sample_dir' => env(
        'MBZ_SAMPLE_DIR',
        'C:/Users/bidsp/Documents/FANNY A R/ASET KPK MOODLE/Paket Modul Kelas E-Learning ASN Berintegritas/1. Moodle Backup (.mbz)'
    ),

];
