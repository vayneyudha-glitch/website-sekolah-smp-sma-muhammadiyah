# Daftar Periksa Persiapan Produksi

Gunakan daftar ini bersama administrator server sebelum membuka layanan. Jangan menaruh nilai rahasia di repositori, tiket, atau dokumen ini.

## Konfigurasi server

- [ ] Atur `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://alamat-resmi-sekolah`, dan buat `APP_KEY` baru yang unik untuk instalasi produksi. Simpan kunci cadangan dengan aman; mengganti kunci setelah layanan aktif dapat membuat sesi dan data terenkripsi tidak terbaca.
- [ ] Aktifkan HTTPS pada server/proxy. Atur `FORCE_HTTPS=true` setelah sertifikat dan proxy siap; saat lokal variabel ini tetap `false`.
- [ ] Atur `SESSION_SECURE_COOKIE=true` dan `SESSION_SAME_SITE=lax` untuk penggunaan web biasa. Gunakan `none` hanya jika integrasi lintas situs memang memerlukannya dan HTTPS selalu aktif.
- [ ] Atur `TRUSTED_PROXIES` hanya ke alamat IP atau rentang proxy tepercaya yang benar-benar berada di depan aplikasi. Jangan percaya semua proxy jika topologi server belum diverifikasi.
- [ ] Pilih `LOG_CHANNEL=daily`, `LOG_DAILY_DAYS=14` atau retensi yang disetujui pengelola, dan `LOG_LEVEL=warning` atau tingkat produksi lain yang disepakati.
- [ ] Atur `QUEUE_CONNECTION` ke backend antrean yang tersedia dan persiapkan worker.
- [ ] Atur mailer produksi, host/port, autentikasi, alamat pengirim, dan uji pengiriman.
- [ ] Pilih penyedia pembayaran melalui `PAYMENTS_DEFAULT_PROVIDER` dan pastikan `PAYMENTS_SANDBOX=false`. Isi kunci rahasia Midtrans atau Xendit di environment server, gunakan endpoint dan kredensial mode produksi, serta cocokkan konfigurasi webhook. Jangan memakai kunci sandbox atau menaruh kunci pada dokumentasi.
- [ ] Pastikan database produksi hanya dapat diakses oleh aplikasi dan cadangan; batasi akun dan jaringan sesuai kebijakan operasional.

## Rilis aplikasi dan database

1. Buat cadangan database dan `storage/app/private` sebelum rilis. Uji pemulihan cadangan pada lingkungan terpisah yang disetujui.
2. Tinjau diff dan setiap migrasi baru, cocokkan dengan versi aplikasi, serta pastikan tidak ada perintah yang menghapus atau mengosongkan data.
3. Aktifkan mode pemeliharaan bila proses rilis memerlukannya, pasang dependensi dari lockfile, lalu jalankan migrasi biasa: `php artisan migrate --force`. Jangan gunakan `migrate:fresh`, `migrate:reset`, atau `db:wipe` di server produksi.
4. Siapkan akun admin memakai `AdminSeeder`. Sediakan `ADMIN_NAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` secara aman pada environment; kata sandi harus memenuhi panjang minimum yang diperiksa seeder. Jalankan `php artisan db:seed --class="Database\Seeders\AdminSeeder" --force`. Lindungi akses ke akun tersebut dan ganti kredensial jika pernah terpapar.
5. Jalankan `php artisan storage:link` untuk berkas yang memang ditujukan untuk publik. Jangan tautkan atau publikasikan `storage/app/private`; berkas absensi, PPDB, dan bukti privat harus tetap di disk privat.
6. Pastikan proses deployment memiliki izin tulis minimum yang diperlukan pada `storage` dan `bootstrap/cache`. Jangan memberi izin tulis luas ke seluruh direktori aplikasi.
7. Setelah verifikasi, akhiri mode pemeliharaan dan periksa health check serta alur utama tanpa memakai data produksi yang tidak diperlukan.

## Proses latar dan tugas terjadwal

- [ ] Jalankan scheduler Laravel setiap menit, misalnya melalui cron: `* * * * * cd /path/aplikasi && php artisan schedule:run >> /dev/null 2>&1`.
- [ ] Jalankan worker antrean terus-menerus di bawah Supervisor atau layanan pengelola proses setara, misalnya `php artisan queue:work --sleep=3 --tries=3 --timeout=90`. Atur restart saat deployment dan pantau kegagalan antrean.
- [ ] Pastikan jadwal menandai alpa murid, mengakhiri tagihan kedaluwarsa, dan membersihkan token tantangan absensi kedaluwarsa berjalan. Jadwal memakai perlindungan agar tidak tumpang tindih dan aman dijalankan berulang.

## Penyimpanan, pencadangan, dan pemantauan

- [ ] Verifikasi berkas publik melalui `storage:link`; uji secara terpisah bahwa berkas privat tidak dapat diakses tanpa otorisasi.
- [ ] Jadwalkan cadangan terenkripsi database dan seluruh `storage/app/private` secara berkala. Batasi retensi, hak akses, dan lokasi penyimpanan cadangan; lakukan uji pemulihan.
- [ ] Health check tersedia pada `/up` dan hanya memberi status sederhana, tanpa kredensial atau rincian internal.
- [ ] Gunakan log harian dengan retensi terbatas. Jangan menulis kata sandi, token, NIK utuh, isi dokumen, selfie, atau koordinat pengguna ke log. Token tantangan disimpan dalam bentuk hash.
- [ ] Batasi akses dan tinjau masa retensi rekaman webhook lama yang dibuat sebelum payload mentah mulai disamarkan; rekaman webhook baru menyimpan penanda redaksi, bukan isi payload.
- [ ] Pastikan log, cadangan, ekspor, dan berkas bukti memiliki izin akses sesuai tugas.

## Pemeriksaan keamanan sebelum publikasi

- [ ] Periksa route dan paket khusus pengembangan pada konfigurasi deployment; jangan mendaftarkan Telescope, debug toolbar, route debug, atau data demo pada server produksi.
- [ ] `DemoDataSeeder` menolak dijalankan kecuali lingkungan `local`. Jangan membuka akses seeder melalui route web.
- [ ] Uji peran: antrean tinjauan hanya untuk admin; selfie guru hanya dapat diunduh oleh admin atau guru pemiliknya.
- [ ] Pastikan header HSTS hanya terkirim saat `FORCE_HTTPS` aktif dan request sudah menggunakan HTTPS. Pastikan proxy yang dipercaya meneruskan protokol klien dengan benar.
