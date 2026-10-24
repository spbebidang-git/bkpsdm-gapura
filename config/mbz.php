<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Folder .mbz milik server ("inbox")
    |--------------------------------------------------------------------------
    |
    | Paket Moodle Backup berukuran ratusan MB, jadi mengunggahnya lewat form
    | web merepotkan. Sebagai gantinya file ditaruh langsung di server (scp /
    | rsync / sftp) ke folder ini, lalu instruktur memilihnya dari wizard impor.
    |
    | Struktur yang dikenali:
    |
    |   <sample_dir>/*.mbz                 → paket full (1 file = semua modul)
    |   <sample_dir>/Versi Per Modul/*.mbz → paket parsial per modul
    |
    | Di produksi letakkan folder ini DI LUAR document root dan cukup beri hak
    | baca kepada user web server, mis. /srv/bkpsdm/mbz-inbox (0750, group
    | www-data). File di luar folder ini selalu ditolak.
    |
    */

    'sample_dir' => env('MBZ_SAMPLE_DIR', storage_path('app/mbz_inbox')),

    /*
    |--------------------------------------------------------------------------
    | Saklar impor dari folder server
    |--------------------------------------------------------------------------
    |
    | Aksesnya sudah dibatasi middleware instruktur. Set false untuk mematikan
    | jalur ini sepenuhnya (wizard hanya menerima unggahan biasa).
    |
    */

    'server_import' => env('MBZ_SERVER_IMPORT', true),

    /*
    |--------------------------------------------------------------------------
    | Umur sesi impor sementara (jam)
    |--------------------------------------------------------------------------
    |
    | storage/app/mbz_imports menampung salinan file yang diunggah sampai
    | langkah konfigurasi selesai. Impor yang ditinggalkan menyisakan file
    | besar di sana, jadi `mbz:purge-temp` (terjadwal harian) membuang yang
    | lebih tua dari nilai ini.
    |
    */

    'temp_ttl_hours' => (int) env('MBZ_TEMP_TTL_HOURS', 24),

];
