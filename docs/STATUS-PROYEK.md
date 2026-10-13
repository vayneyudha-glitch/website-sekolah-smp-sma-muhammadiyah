# Status Proyek: Sistem Informasi SMP SMA Muhammadiyah Subang

## Lingkungan
- Laravel 13, PHP 8.3 (Laragon), MySQL Laragon port 3307,
  database: website-smp-sma-muhmamadiyah-subang. Kasir YUMAERA terpisah
  (XAMPP port 3306), jangan diganggu.
- Menjalankan lokal (Terminal Laragon, masuk ke folder proyek):
  php artisan serve | npm run dev | php artisan queue:work
- Tes otomatis: php artisan test (SQLite memori, aman).

## Aturan agent (wajib)
Lihat CLAUDE.md dan AGENTS.md: jangan memasang software (PHP, Composer,
Boost), jangan menjalankan terminal/migrate/tes, jangan menyentuh .env,
jangan membuat branch atau commit, jangan menyentuh folder lain.
Pemilik menjalankan migrate, tes, dan commit sendiri.

## Sudah dibuat
Peran dan dashboard, data induk, jadwal, pengumuman, guru publik,
profil sekolah/fasilitas/galeri, PPDB (daftar, dokumen privat,
verifikasi, keputusan, konversi siswa, ekspor, PDF), pembayaran
(Midtrans/Xendit, webhook, kuitansi, rekap), absensi murid dan guru
(GPS, selfie, geofence), tugas dan nilai, dashboard, ekspor
Excel/PDF, notifikasi (database + email; WhatsApp masih log),
privasi, halaman error, audit log, seeder demo (lokal saja).

## Belum diuji manual
Alur PPDB setelah mendaftar, absensi murid dan guru, ekspor
Excel/PDF, pembayaran sandbox, notifikasi email.

## Berikutnya
Prompt 8 (penguatan absensi, kesiapan produksi, panduan),
Prompt 9 (impor Excel, izin/sakit, kenaikan kelas, rapor),
lalu uji manual per fitur, data asli sekolah, hosting dan HTTPS.
## Posisi terakhir (13 Oktober 2026)
Prompt 8 dan 9 sudah dikerjakan Agent, tapi migrate, php artisan test,
dan uji manual BELUM dijalankan setelahnya. Navbar admin masih perlu
dirapikan (menu terlalu banyak).