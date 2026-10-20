# Log dan backup Gapura

Implementasi menggunakan log JSON dengan redaksi sebelum penulisan dan backup terenkripsi age. Kunci privat pemulihan tidak diperlukan oleh aplikasi. Backup disimpan di server aplikasi pada `storage/app/backups`, tanpa kewajiban penyimpanan luar server. Backup belum aktif sampai age, recipient, pengiriman alert, cron, serta uji restore produksi selesai dikonfigurasi. Salinan pada server yang sama tidak melindungi dari kehilangan seluruh server atau disk.

## Aktivasi produksi

1. Pasang `age`, `age-keygen`, `mysqldump` yang kompatibel dengan server MySQL, `tar`, dan ekstensi PHP zlib. Instal dependensi melalui `composer install` dari lockfile. Tetapkan `BACKUP_AGE_BINARY` ke executable tepercaya yang tidak dapat diubah oleh akun web.
2. **Pada mesin pemulihan terpisah**, jalankan:

   ```sh
   umask 077
   age-keygen -o gapura-recovery.agekey
   age-keygen -y gapura-recovery.agekey
   ```

   Simpan identitas privat pada dua lokasi aman terpisah, dengan akses terbatas dan pengujian pembukaan berkala. Salin hanya recipient publik `age1...` ke `BACKUP_AGE_RECIPIENTS`. Beberapa recipient dipisahkan koma; masing-masing dapat mendekripsi backup. Jangan simpan identitas privat di `.env`, Git, server aplikasi, chat, atau bucket backup. Kunci backup tidak bergantung pada `APP_KEY`.
3. Gunakan penyimpanan lokal privat `storage/app/backups`. Biarkan `BACKUP_REMOTE_DISK` kosong dan gunakan `BACKUP_REQUIRE_REMOTE=false`, termasuk pada produksi. Pastikan volume cukup besar dan akun scheduler mempunyai izin tulis. Direktori backup bukan bagian dari `public/` atau symlink `public/storage`.
4. Simpan identitas privat pemulihan di tempat aman terpisah; aplikasi hanya memerlukan recipient publik. Enkripsi, manifest, pemeriksaan ukuran/checksum saat unduhan, rotasi, dan penguncian proses tetap berlaku untuk backup lokal. Verifikasi kapasitas disk melalui monitoring server.
5. Setel `BACKUP_ALERT_EMAIL` dan mailer SMTP/layanan sungguhan. `log`/`array` bukan pengiriman alert. `backup:health` menolak menganggap produksi sehat bila alert belum siap. Alert juga ditulis ke stderr untuk pengawas proses. Pasang monitor **di luar aplikasi** yang mendeteksi nonzero exit, cron yang berhenti, alert yang tidak terkirim, disk hampir penuh, dan tidak adanya backup baru. Health check hourly dapat mengirim alert berulang sampai masalah selesai.
6. Gunakan `APP_ENV=production`, `APP_DEBUG=false`, `DEV_ADMIN_BYPASS=false`, `LOG_STACK=daily`, `LOG_LEVEL=info`. Gunakan cache bersama yang mendukung atomic lock, misalnya Redis/database, untuk `onOneServer()`. Scheduler dan perintah backup dijalankan oleh satu akun OS khusus dengan akses minimum. Web server hanya melayani direktori `public/`.
7. Jalankan migration baru untuk mendaftarkan `backup.download`, lalu `php artisan config:cache`. Permission itu tidak otomatis diberikan kepada administrator biasa; akses halaman tetap memerlukan `log.view`. Super admin memiliki akses. Unduhan diaudit; hanya backup `.age` dengan manifest dan checksum cocok yang disajikan. Unduhan log tersaring; ZIP/SQL/GZIP lama tidak dilayani lewat UI.
8. Jalankan perintah backup pertama secara berurutan, lalu health check:

   ```sh
   php artisan logs:backup
   php artisan db:backup
   php artisan db:backup --weekly
   php artisan files:backup
   php artisan backup:health
   ```

   Tambahkan `* * * * * cd /path/to/gapura && php artisan schedule:run` ke pengawas/cron server dan tangkap status/output lewat monitoring. Jangan membuang seluruh output ke `/dev/null` tanpa monitor lain. Health check tidak boleh dianggap pengganti pengawas cron independen.

## Perilaku dan batasan

- Backup log pukul 01:30, DB harian 02:00, DB mingguan Senin 03:00, dan berkas 03:45, zona Asia/Jakarta. Semua memiliki scheduler lock dan file lock lokal. Scheduler tidak otomatis retry di luar jadwal; monitor harus memberi tahu kegagalan dan operator dapat menjalankan ulang perintah yang gagal.
- Backup menggunakan nama unik. Hasil enkripsi dipublikasikan dengan rename atomik. Penyimpanan default hanya lokal. Jika penyimpanan remote diaktifkan secara eksplisit, salinan remote dibaca kembali untuk verifikasi SHA-256 dan ukuran sebelum manifest lokal diterbitkan dan retensi dijalankan. Kegagalan remote membuat command gagal; salinan terenkripsi lokal yang belum berstatus selesai dapat tertinggal untuk investigasi. Remote manifest adalah metadata, bukan tanda tangan digital; checksum bukan perlindungan terhadap penyerang yang dapat mengganti keduanya. age memverifikasi autentikasi ciphertext saat dekripsi; Object Lock dan isolasi akun melindungi riwayat dari manipulasi.
- Retensi lokal menghitung jumlah backup selesai per jenis, bukan umur hari. Default: 7 daily, 4 weekly, 7 files, 30 logs. Retensi remote dikelola bucket; aplikasi tidak menghapus remote maupun backup plaintext lama.
- Dump DB ditulis bertahap ke GZIP, bukan ditampung seluruhnya di memori. Kompresi sementara dan option file MySQL berada di direktori acak `0700`, berkas `0600`, lalu dihapus di `finally`. **Ada staging plaintext terkompresi selama proses berjalan.** Gunakan volume terenkripsi atau tmpfs yang cukup besar untuk direktori backup, swap terenkripsi, dan `UMask=0077` pada service. SIGKILL/power loss bisa meninggalkan `.work-*`; setelah memastikan tidak ada job berjalan, operator harus menangani direktori itu dengan akses terbatas. Penghapusan biasa tidak menjamin secure erase SSD/snapshot. Jangan mengambil snapshot volume staging tanpa kebijakan enkripsi.
- `--single-transaction` memberi snapshot konsisten untuk InnoDB; pastikan tabel transaksional dan hindari DDL selama dump. MyISAM memerlukan strategi snapshot/lock tambahan. Akun dump perlu SELECT, SHOW VIEW, TRIGGER, EVENT, serta akses routines sesuai versi MySQL. DB/files diambil pada waktu berbeda; untuk pemulihan konsisten lintas keduanya gunakan quiesce penulisan atau snapshot terkoordinasi pada infrastruktur.
- Backup berkas mencakup `storage/app/public`, `public/assets/media`, dan `media` (lihat `config/backup.php`). Sesuaikan daftar bila ada penyimpanan upload lain, objek cloud, atau sertifikat di lokasi lain. Sumber yang hilang atau di luar aplikasi menyebabkan kegagalan, bukan dilewati diam-diam. Symlink tidak didereferensi. `tar` yang mendeteksi berkas berubah dianggap gagal; untuk aplikasi sibuk gunakan snapshot volume/quiesce.
- Source code dipulihkan dari Git/release terverifikasi. `.env` dan `APP_KEY` harus di-escrow dalam secret manager terpisah, karena token dalam database terenkripsi dengan APP_KEY lama tidak dapat dibaca tanpa kuncinya. Aplikasi sengaja tidak mengemas `.env` bersama backup.
- Log JSON menyaring key sensitif, objek, exception, pola token, NIP, email, dan secret konfigurasi yang dikenal. Filter adalah lapisan tambahan: jangan menambahkan log request/payload/response penuh. Nilai sensitif dengan format/key baru tetap memerlukan review. Detail audit mencakup auth events Laravel, mutasi admin, status, actor ID, request ID, serta unduhan; ini bukan audit perubahan setiap kolom database atau seluruh alur SSO khusus. Local audit dapat diubah oleh akun aplikasi; untuk bukti tahan manipulasi kirim log melalui agen OS ke kolektor terpisah dengan akses/retensi independen.
- Log/backup lama tidak diubah secara otomatis. Setelah backup baru lulus restore, migrasikan arsip lama ke penyimpanan terenkripsi sesuai retensi; evaluasi rotasi kredensial jika arsip lama pernah memuat secret. Jangan hapus satu-satunya backup yang masih dapat dipulihkan.
- Target awal jadwal harian adalah kehilangan data maksimum sekitar 24 jam saat backup sukses. Jika dibutuhkan RPO menit, tambahkan binlog/PITR terkelola dan uji restore point-in-time. RTO harus diukur lewat latihan, bukan diasumsikan.

## Penyimpanan luar server (opsional)

Konfigurasi aktif yang dituju adalah lokal saja: `BACKUP_REMOTE_DISK=` dan `BACKUP_REQUIRE_REMOTE=false`. Tidak ada unggahan remote ketika disk kosong. Bila kebutuhan berubah, dukungan remote tetap tersedia melalui disk privat `backup_s3`/SFTP dan `BACKUP_REQUIRE_REMOTE=true`. Aktifkan hanya setelah bucket, kredensial, retensi dan pengujian disiapkan; konfigurasi produksi memeriksa disk privat dan endpoint HTTPS. Retensi remote dikelola penyedia, bukan rotasi lokal aplikasi.

## Uji pemulihan (mesin terisolasi)

Lakukan setelah perubahan skema/deployment penting dan secara berkala (misalnya bulanan). Gunakan salinan arsip lokal beserta manifest atau unduhan versi yang tepercaya, identitas pemulihan offline, serta server MySQL disposable; jangan jalankan import percobaan terhadap database aktif.

1. Salin `.age` dan manifest `.age.json` dari `storage/app/backups/<jenis>/` ke mesin pemulihan tepercaya. Periksa `sha256sum FILE.age` sesuai manifest. Checksum mendeteksi kerusakan; age juga menolak ciphertext terpotong/diubah saat dekripsi.
2. Pada direktori pemulihan privat dengan ruang memadai:

   ```sh
   umask 077
   age --decrypt -i /secure/gapura-recovery.agekey -o restored.sql.gz FILE.age
   gzip -t restored.sql.gz
   ```

   Lanjutkan import hanya jika kedua perintah keluar dengan kode 0. Dekripsi penuh ke file terlebih dahulu menghindari import SQL parsial jika verifikasi age gagal di bagian akhir.
3. Import ke database disposable kosong yang dibuat untuk latihan: `mysql --defaults-extra-file=/secure/restore.cnf recovery_test < restored.sql` setelah dekompresi berhasil. Pastikan `restore.cnf` menunjuk hanya ke host latihan. Verifikasi migrasi, jumlah record/tabel penting, relasi, akun/peran, hasil belajar dan sertifikat, bukan sekadar exit code import.
4. Untuk arsip log/berkas: dekripsi menjadi `.tar.gz`, jalankan `gzip -t`, periksa `tar -tzf`, lalu ekstrak **sebagai pengguna tanpa privilege ke direktori kosong terisolasi**. Tinjau path/symlink sebelum menyalin ke aplikasi pemulihan. Jangan mengekstrak langsung di atas deployment aktif.
5. Pulihkan APP_KEY/config melalui secret manager, jalankan aplikasi pemulihan tanpa pengiriman email/SSO/sertifikat produksi, dan uji login, materi, unggahan, serta rekam belajar. Catat waktu, versi aplikasi, backup yang dipakai, kehilangan data, dan hasil uji. Bersihkan data latihan sesuai kebijakan.

Tes otomatis lokal: `TEST_AGE_BINARY=/path/to/age php artisan test --filter=LogBackupTest`. `age-keygen` harus berada di direktori binary yang sama. Tes memakai storage sementara dan tidak menghapus arsip aplikasi. Tanpa age, bagian enkripsi nyata ditandai skipped; itu tidak membuktikan kesiapan produksi.

Referensi: [OWASP Logging](https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html), [CISA ransomware guide](https://www.cisa.gov/stopransomware/ransomware-guide), [age CLI](https://github.com/FiloSottile/age/blob/main/doc/age.1.html).

Untuk pengujian otomatis lengkap dengan dump dan restore MySQL nyata pada **database sementara**, jalankan `TEST_AGE_BINARY=/path/to/age php scripts/test-backup-security.php`. Script hanya berjalan pada environment local/testing, memerlukan izin CREATE/DROP DATABASE, dan membersihkan database percobaan sesudah selesai. Opsi `--all` menjalankan seluruh suite aplikasi dengan isolasi yang sama. Jalankan dari lingkungan pengembangan, bukan server produksi.

## Hasil verifikasi implementasi — 16 Oktober 2026

- Suite terisolasi untuk log/backup dan autentikasi: **38 tes, 150 assertion, lulus**, termasuk enkripsi/dekripsi age, restore MySQL nyata, arsip berkas tanpa mengikuti symlink, penolakan akses, kegagalan remote, checksum, dan fallback logger.
- Pemeriksaan TypeScript dan build client/SSR berhasil.
- Pemeriksaan suite aplikasi yang lebih luas masih mencatat **22 kegagalan** pada validasi/hak akses/profil dan fitur lain. Hasil ini tidak menjadi sertifikasi keamanan aplikasi secara keseluruhan.
- Pada verifikasi 16 Oktober, bucket nyata, Object Lock, alert eksternal, cron produksi dan latihan pemulihan data produksi belum diverifikasi. Kebijakan saat ini menggunakan backup lokal; bucket dan Object Lock bukan prasyarat aktivasi. Arsip lama belum dikonversi. Age yang digunakan untuk tes berada di lingkungan pengujian; server deployment tetap harus memasang binary tepercaya sesuai langkah aktivasi.

## Pencatatan dan pembacaan log aplikasi

Log baru berupa JSON satu kejadian per baris, dengan `datetime` berzona waktu, `level_name`, pesan singkat, `context.event`, serta konteks aman. Nama event stabil, misalnya `certificate.push.completed`; pesan untuk operator menggunakan bahasa Indonesia. Event paket/framework yang tidak memberi event sendiri mendapat `application.<level>` atau `application.exception`.

- `daily`: kejadian/error aplikasi (termasuk error peserta), `auth`: autentikasi, `audit`: permintaan admin dan unduhan, `backup`: operasi backup. Alias `app_errors` menulis ke kanal daily untuk kompatibilitas. File app-errors lama tetap dapat dibaca.
- Retensi dikonfigurasi lewat `LOG_DAILY_DAYS=14`, `LOG_AUTH_DAYS=30`, `LOG_AUDIT_DAYS=30`, `LOG_BACKUP_DAYS=30`; tingkat kanal aplikasi mengikuti `LOG_LEVEL=info`. Rotasi Monolog berjalan saat kanal menulis; ini bukan janji pembersihan file lama pada kanal yang tidak lagi aktif. Jangan menghapus arsip historis tanpa kebijakan organisasi.
- Setiap request web/API mendapat UUID baru buatan server dan header respons `X-Request-ID`, termasuk respons error. Header ID dari klien tidak dipercaya. Audit menggunakan ID yang sama. Command dan job mendapat `process_id`; `parent_process_id` menautkan proses pemanggil, sedangkan `request_id` diteruskan melalui payload antrean yang hanya berisi ID korelasi. Konteks dipulihkan setelah command/job/request selesai atau gagal. Worker tetap perlu direstart setelah rilis.
- Integrasi sertifikat hanya mencatat operasi, endpoint tanpa userinfo/query/fragment, percobaan, ID pertukaran, durasi, status HTTP, ID internal, dan jumlah hasil pada ringkasan proses. Parameter, isi respons, serta sampel peserta tidak dicatat, termasuk saat debug. `post-certificate-last.txt` tidak dibuat lagi; file historis yang sudah ada tidak dihapus otomatis.
- Exception dicatat oleh penangan yang bertanggung jawab, dengan kelas, pesan tersaring, file relatif, nomor baris, serta maksimum 20 frame tanpa argumen. Detail exception bukan pesan untuk pengguna. Laporan berulang untuk instance exception yang sama dicegah oleh Laravel.
- Redaksi berjalan sebelum interpolasi dan pada formatter akhir. Konteks dibatasi 12 tingkat, 100 elemen per array, dan 1.000 simpul per traversal. String sangat besar dihilangkan; teks dibatasi 16.000 karakter. Record file maksimal 48 KiB dan tetap JSON valid; konteks yang melampaui batas diganti penanda `truncated`. File baru memakai mode `0600` dan locking. Sesuaikan akun proses web/worker agar keduanya mempunyai akses yang dibutuhkan tanpa melonggarkan izin file.

Contoh pencatatan aman:

```php
Log::info('Pengiriman sertifikat selesai.', [
    'event' => 'certificate.push.completed',
    'success_count' => 12,
    'skipped_count' => 0,
    'failed_count' => 0,
]);
```

Di halaman **Log dan backup**, pilih file, tingkat log, teks pencarian, atau UUID permintaan. Tampilan memperlihatkan waktu WIB, tingkat keparahan, pesan, tombol salin ID, dan konteks yang dapat dibuka. Pencarian hanya memindai maksimal 512.000 byte di akhir file dan menampilkan maksimal 200 hasil terbaru; ini bukan pencarian seluruh riwayat. Gunakan ID proses dalam pencarian teks untuk menelusuri command/job. Tombol **Terapkan / Muat ulang** memuat data terbaru; halaman tidak melakukan polling otomatis.

Pembaca dan unduhan memakai redaksi yang sama: JSON diparse sebelum key sensitif disaring. Baris teks lama disaring; JSON rusak dan baris unduhan di atas 64 KiB dihilangkan. Unduhan mencakup file terpilih, bukan hanya hasil filter tampilan. HTML di dalam pesan selalu dirender sebagai teks. Akses tetap memerlukan `log.view`; unduhan backup tetap memerlukan hak tambahan. Respons memakai `private, no-store`.

Saat rilis, jalankan deployment biasa, lalu `npm ci`, `npm run typecheck`, dan `npm run build` di server. Build membuat `public/build`, menyalin aset statis dari `resources/static/public/assets` ke `public/assets`, dan menyalin ikon Boxicons ke `public/vendor/boxicons`. Ketiga direktori keluaran itu tidak dilacak Git. Cadangkan berkas unggahan di `public/assets/media` sebelum deployment pertama yang melepas aset lama dari Git; build tidak menghapus berkas unggahan. Perbarui konfigurasi dengan `php artisan config:cache`, lalu `php artisan queue:restart` untuk driver queue persisten. Pastikan pengawas proses memulai kembali worker. Verifikasi header request ID dan satu kejadian audit setelah rilis.

Pengujian terarah: `vendor/bin/phpunit --filter 'SecureLoggingTest|CertificateHttpLoggerTest|LogBackupTest|CertificateIntegrationTest'`. Gunakan database MySQL khusus pengujian yang boleh direset oleh `RefreshDatabase`, bukan database aplikasi. Uji enkripsi/restore pada `LogBackupTest` memerlukan dependensi yang dijelaskan di atas; pengujian dapat dilewati bila dependensinya tidak tersedia.
