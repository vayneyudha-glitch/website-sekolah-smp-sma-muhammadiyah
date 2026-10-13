<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Administrasi Sekolah

Portal admin menyediakan pengelolaan tahun ajaran aktif, kelas SMP/SMA dan wali kelas, mata pelajaran, murid, guru, akun, hubungan orang tua-anak, jadwal, pengumuman, dan audit aktivitas. Akun yang dibuat admin mendapat kata sandi sementara sekali tampil dan wajib menggantinya saat login pertama. Pengumuman dapat ditujukan untuk publik atau peran tertentu.

Untuk menyiapkan fitur pada lingkungan lokal, jalankan migrasi baru menggunakan koneksi sekolah yang telah dikonfigurasi dan buat tautan berkas publik untuk foto guru serta gambar berita:

```bash
php artisan migrate
php artisan storage:link
```

Nomor WhatsApp sekolah dibaca dari `SCHOOL_WHATSAPP` pada konfigurasi aplikasi. Isi nilainya dengan kode negara dan nomor telepon, hanya angka (contoh: `6281234567890`).

## Absensi Sekolah

Administrator mengatur koordinat sekolah dengan memilih titik pada peta Leaflet/OpenStreetMap, radius (default 100 meter), serta jendela waktu absen Hadir murid dan absen masuk guru. Murid hanya dapat mengirim status Hadir jika waktu server berada dalam jendela yang diatur dan perhitungan jarak Haversine di server berada dalam radius sekolah; status Izin/Sakit memerlukan alasan dan dapat disertai lampiran privat. Setiap murid hanya memiliki satu catatan per hari. Perintah terjadwal `attendance:mark-absent` mengisi status Alpa bagi murid aktif yang belum tercatat pada pukul 23:59.

Guru melakukan absen masuk dan pulang menggunakan lokasi serta selfie. Bukti disimpan di disk privat dan metadata absensi menggunakan waktu server, koordinat, akurasi GPS, alamat IP, serta hash pengenal perangkat. Penanda akurasi GPS rendah, lompatan lokasi, dan pemakaian perangkat bersama hanya perkiraan untuk membantu tinjauan administrator; penanda tersebut tidak memblokir absensi. Guru dapat melihat rekap bulanan kelas yang diampu dan mengoreksi status dengan alasan wajib; koreksi dicatat di audit aktivitas. Orang tua dapat melihat rekap kehadiran anak yang tertaut. Administrator dapat mengatur absensi dan mengunduh rekap bulanan guru sebagai CSV UTF-8 yang kompatibel dengan Excel.

Fitur absensi memerlukan migrasi baru `2026_10_12_120000_create_attendance_tables.php` dan penjadwal Laravel harus berjalan agar penandaan Alpa otomatis diproses. Berkas lampiran dan selfie tidak boleh dipublikasikan melalui tautan penyimpanan publik. GPS browser hanya berfungsi di HTTPS atau `localhost`; jika izin lokasi ditolak, izinkan lokasi melalui pengaturan browser.

## Penerimaan Peserta Didik Baru

Informasi PPDB publik tersedia di `/ppdb`. Administrator dapat mengatur status buka/tutup, jadwal gelombang, alur, dan biaya dari menu PPDB. Calon murid membuat akun pendaftar, menyimpan data bertahap sebagai draf, mengunggah berkas ke penyimpanan privat, dan memantau hasil verifikasi serta keputusan akhir. Pendaftar yang diterima berubah menjadi murid aktif setelah admin memilih kelas sesuai jenjang.

Untuk mengaktifkan fitur, jalankan migrasi baru `2026_10_12_110000_create_ppdb_tables.php` melalui koneksi sekolah setelah meninjau konfigurasi lingkungan. Berkas pendaftaran menggunakan disk `local` privat; jangan membuat tautan publik ke direktori penyimpanan ini. Ekspor data tersedia sebagai CSV UTF-8 yang dapat dibuka di Excel, dan bukti pendaftaran disediakan sebagai PDF.

## Dasbor, Ekspor, Notifikasi, dan Privasi

Dasbor menampilkan ringkasan sesuai peran akun. Pusat ekspor admin dan guru tersedia dari navigasi; file Excel diproses bertahap dan PDF membatasi hasil hingga 1.000 baris sehingga laporan besar perlu difilter. Ekspor pembayaran memakai kelas `PaymentsExport` yang sama dengan fitur pembayaran.

Ekspor XLSX dan PDF memerlukan paket `maatwebsite/excel` dan `barryvdh/laravel-dompdf`. Notifikasi Laravel menggunakan database dan email, serta mengantrekan pengiriman melalui koneksi queue yang dikonfigurasi. Pada lingkungan produksi, jalankan worker queue agar email dan notifikasi diproses. Penyedia WhatsApp hanya mencatat metadata non-pesan ke log; `WHATSAPP_PROVIDER` kosong atau `log` memilih implementasi default. Adapter pihak ketiga belum disediakan.

Kebijakan Privasi dan Syarat Penggunaan tersedia pada navigasi publik. Pendaftaran PPDB mencatat versi dan waktu persetujuan privasi. Pengiriman absensi dengan lokasi meminta persetujuan tersendiri dan mencatat waktu persetujuan. Masa retensi yang rinci dan kanal kontak resmi perlu disesuaikan dengan kebijakan arsip dan kontak sekolah sebelum publikasi final.

Migrasi `2026_10_12_150000_add_privacy_and_notification_preferences.php` menambahkan preferensi email, waktu persetujuan, dan tabel notifikasi. Tinjau migrasi sebelum menerapkannya pada database sekolah. Skenario uji manual: buka dasbor dengan akun admin/guru/murid/orang tua yang berbeda; pastikan hanya data terkait akun yang tampil; coba buka ekspor admin dengan akun bukan admin; buat notifikasi dan verifikasi hanya penerima yang benar melihatnya; uji preferensi email; lakukan pendaftaran PPDB tanpa persetujuan dan dengan persetujuan; lakukan absensi hadir dengan dan tanpa persetujuan lokasi; unduh Excel/PDF dengan filter tanggal/kelas serta pastikan XLSX mengamankan nilai yang diawali karakter formula.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
