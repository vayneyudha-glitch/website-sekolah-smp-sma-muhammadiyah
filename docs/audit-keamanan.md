# Laporan Audit Keamanan dan Kualitas

## Ringkasan

Audit statis menemukan satu temuan terverifikasi tingkat **sedang** pada validasi GPS absensi. Ada pula satu perhatian konfigurasi sesi yang bergantung pada deployment; informasi yang tersedia tidak cukup untuk mengonfirmasinya sebagai kerentanan. Tidak ada kode yang diubah. Perintah terminal, pengujian, dan pembacaan `.env` tidak dilakukan.

## Temuan

### 1. Koordinat GPS absensi dipercaya dari permintaan klien

- **Tingkat:** Sedang
- **File dan baris:** [AttendanceController.php](../app/Http/Controllers/AttendanceController.php#L47), khususnya baris 47–74 dan 267–290
- **Penjelasan:** Pemeriksaan geofence menggunakan koordinat yang dikirim oleh klien. Pemeriksaan rentang koordinat dan persetujuan lokasi tidak membuktikan koordinat berasal dari lokasi perangkat yang sebenarnya.
- **Skenario:** Murid yang sudah masuk dapat mengirim permintaan absensi langsung dengan koordinat sekolah meskipun berada di tempat lain, lalu tercatat hadir. Alur absensi guru memiliki asumsi kepercayaan yang sama. Selfie dan token perangkat yang diperiksa tidak membuktikan lokasi GPS.
- **Keyakinan:** 10/10
- **Saran:** Jangan jadikan koordinat dari klien sebagai satu-satunya bukti kehadiran fisik; gunakan verifikasi tambahan yang independen atau proses peninjauan.

## Perhatian yang belum terverifikasi

- **Konfigurasi cookie sesi:** [session.php](../config/session.php#L172) membuat atribut `secure` bergantung pada `SESSION_SECURE_COOKIE` tanpa nilai aman bawaan pada konfigurasi tersebut. Jika pengaturan itu tidak aktif dan deployment menerima HTTP, cookie sesi mungkin terkirim tanpa atribut `Secure`. Karena `.env` dan perilaku deployment tidak diperiksa, kondisi ini belum dapat dipastikan dan tidak dihitung sebagai temuan terverifikasi.

## Cakupan pemeriksaan

- **Otorisasi dan IDOR:** Meninjau grup route serta pemeriksaan controller/policy yang relevan di [web.php](../routes/web.php#L24) dan [auth.php](../routes/auth.php#L15). Tidak ditemukan masalah otorisasi tambahan yang dapat diverifikasi dalam peninjauan ini. Pemeriksaan mencakup pembatasan peran admin/guru serta pemeriksaan peran, hubungan, atau kepemilikan pada alur murid, orang tua, PPDB, tagihan, notifikasi, dan lampiran privat.
- **Unggahan dan file privat:** Meninjau alur PPDB, pengumpulan tugas, bukti pembayaran, absensi, dan konten sekolah. Tidak ditemukan masalah tambahan yang dapat diverifikasi; alur yang diperiksa menggunakan penyimpanan privat dan handler unduhan terlindungi untuk dokumen privat, serta validasi gambar untuk unggahan konten publik.
- **Absensi GPS:** Menemukan masalah kepercayaan koordinat yang dijelaskan di atas. Peninjauan juga melihat validasi koordinat, perhitungan geofence, penyimpanan lokasi, dan alur selfie/token perangkat.
- **Pembayaran dan webhook:** Tidak ditemukan masalah tambahan yang dapat diverifikasi. Peninjauan mencakup pengecualian CSRF callback, verifikasi callback, pencocokan referensi dan nominal, serta pembaruan status transaksional.
- **Autentikasi dan sesi:** Tidak ditemukan masalah autentikasi tambahan yang dapat diverifikasi. Peninjauan mencakup throttling login, reset/perubahan kata sandi, verifikasi email, status akun, dan regenerasi sesi. Perhatian atribut cookie sesi dicatat terpisah karena bergantung pada deployment.
- **Injeksi, XSS, dan ekspor:** Tidak ditemukan masalah tambahan yang dapat diverifikasi pada output Blade yang diperiksa, konstruksi query, atau mitigasi formula pada ekspor spreadsheet.
- **Privasi dan logging:** Tidak ditemukan kebocoran tambahan yang dapat diverifikasi pada area yang ditinjau; pemeriksaan mencakup lampiran privat, ekspor absensi, audit record, dan logging notifikasi.
- **Kualitas, transaksi, indeks, dan penanganan error:** Tidak ditemukan cacat tambahan yang dapat diverifikasi dan relevan dengan keamanan pada area yang ditinjau. Pemeriksaan statis ini bukan profiling runtime.
- **Tes dan konfigurasi pengujian:** Meninjau tes terkait autentikasi, peran, tugas, dan pembayaran, serta [phpunit.xml](../phpunit.xml). Tidak ditemukan masalah konfigurasi database tes yang dapat dikonfirmasi dari peninjauan ini. Tes tidak dijalankan; tes yang diperiksa tidak mencakup koordinat GPS palsu.
- **Konfigurasi produksi:** Perhatian konfigurasi sesi dicatat di atas. Pemeriksaan tidak mengonfirmasi nilai konfigurasi runtime atau lingkungan deployment.

## Batasan

Hasil ini berasal dari peninjauan kode secara statis. Tidak ada perintah, tes, atau pemindaian dinamis yang dijalankan, dan `.env` tidak dibaca. Pernyataan “tidak ditemukan” berarti tidak ada masalah tambahan yang dapat diverifikasi pada area yang ditinjau, bukan jaminan bahwa area tersebut bebas kerentanan.
