<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Menjalankan aplikasi di Laragon

Gunakan PHP 8.3 dari Laragon dan database MySQL proyek pada port `3307` dengan nama `website-smp-sma-muhmamadiyah-subang`. Salin `.env.example` menjadi `.env` bila belum tersedia, isi konfigurasi lokal pada berkas `.env` milik Anda, lalu buat kunci aplikasi. Jangan gunakan data rahasia pada repositori.

Pasang dependensi yang tercantum di manifest proyek, lalu siapkan database dan tautan penyimpanan:

```bash
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan storage:link
```

Jalankan tiga proses berikut di terminal Laragon yang terpisah:

```bash
php artisan serve
npm run dev
php artisan queue:work
```

Jalankan seluruh tes dengan `php artisan test`. Daftar persiapan server produksi tersedia di [docs/go-live.md](docs/go-live.md).

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

Nomor WhatsApp utama diatur dari **Profil Sekolah**. `SCHOOL_WHATSAPP` tetap menjadi nilai cadangan bila kolom profil belum diisi; gunakan kode negara dan nomor telepon, hanya angka (contoh: `6281234567890`).

## Impor, izin/sakit, kenaikan kelas, dan rapor

Migrasi `2026_10_13_183700_create_school_workflows.php` menambahkan penyimpanan pratinjau impor, riwayat kelas, permohonan izin/sakit, komponen nilai, dan rapor. Setelah meninjau migrasi serta memastikan koneksi Laragon yang dipilih adalah database proyek pada port `3307`, jalankan:

```bash
php artisan migrate
```

Tidak ada variabel environment baru. Fitur memakai paket Excel dan DomPDF yang sudah tercantum pada `composer.json`. Lampiran izin/sakit disimpan pada disk `local` privat; jangan buat tautan storage publik untuk lampiran tersebut.

**Impor (admin):** buka **Impor Data Excel**, unduh templat sesuai jenis, lalu isi sheet `Data` mengikuti sheet `Petunjuk`. Impor murid sebelum orang tua agar NISN anak dapat ditautkan. Gunakan ID kelas dan tahun ajaran dari panel admin; maksimal 2.000 baris dan 10 MB per unggahan. Pratinjau menampilkan nomor baris dan kesalahan tanpa menampilkan data pribadi. Tidak ada data yang tersimpan sampai dikonfirmasi. Akun murid/guru/orang tua menerima kata sandi acak; unduh CSV kata sandi satu kali dan serahkan secara aman. Akun wajib mengganti kata sandi saat login pertama. Pratinjau kedaluwarsa setelah 30 menit; scheduler mengosongkan payload dan kata sandi sementara yang kedaluwarsa setiap jam.

**Izin/sakit:** murid mengajukan dari menu Izin/Sakit. Orang tua memilih anak yang telah ditautkan, lalu mengisi tanggal, alasan, dan lampiran opsional (JPG/PNG/PDF, maksimal 5 MB). Guru yang mengampu kelas atau wali kelas, serta admin, dapat memutuskan dengan catatan. Permohonan yang disetujui dicatat ke rekap absensi sebagai Izin/Sakit; lampiran hanya dapat diunduh melalui pemeriksaan akses.

**Tahun ajaran:** buat tahun ajaran dan kelas tujuan terlebih dahulu pada menu Akademik, lalu buka **Kenaikan Kelas**. Petakan kelas, atur tujuan khusus bagi murid yang mengulang, tinjau pratinjau, dan konfirmasi. Lulusan menjadi alumni dan akunnya dinonaktifkan; riwayat kelas lama tetap tersedia.

**Nilai dan rapor:** admin/guru membuka Nilai/Rapor dari navigasi. Guru hanya dapat mengelola kelas dan mata pelajaran pada jadwalnya; wali kelas mengisi catatan dan menerbitkan rapor. Bobot komponen harus berjumlah 100%. Nilai tugas yang telah dinilai ikut dihitung pada komponen `Tugas`. Murid dan orang tua hanya melihat rapor terbit. Perubahan data sumber atau isi rapor setelah penerbitan memerlukan alasan dan dicatat pada audit. PDF memakai profil sekolah sebagai kop dan rekap absensi untuk periode tahun ajaran.

**Pemeriksaan manual:** (1) impor baris valid dan tidak valid; pastikan pratinjau tidak menyimpan data sebelum konfirmasi, akun memakai kata sandi acak, dan unduhan kata sandi kedua ditolak; (2) ajukan izin dengan lampiran, tinjau menggunakan guru kelas yang benar, pastikan guru di luar kelas ditolak dan izin/sakit yang disetujui tidak berubah menjadi Alpa; (3) naikkan kelas, atur satu murid mengulang dan satu murid lulus, lalu periksa riwayat tahun lama, kelas baru, dan akses alumni; (4) atur bobot/nilai, terbitkan rapor, pastikan murid/orang tua hanya melihat rapor terbit, PDF memuat ringkasan hadir/izin/sakit/alpa, dan perubahan tanpa alasan ditolak.

## Absensi Sekolah

Administrator mengatur koordinat sekolah, radius, ambang akurasi GPS, serta jendela waktu absensi. Sebelum absensi Hadir dikirim, browser meminta tantangan sekali pakai yang terikat akun, sesi, dan jenis absensi. Server memakai waktu sendiri, memeriksa rentang koordinat, dan menandai akurasi buruk, loncatan lokasi, lokasi/perangkat berulang, waktu di luar jadwal, atau lokasi di luar radius setelah toleransi akurasi untuk ditinjau admin. Penanda adalah sinyal pemeriksaan, bukan bukti pasti GPS palsu. Status Izin/Sakit meminta alasan dan dapat disertai lampiran privat. Setiap murid hanya memiliki satu catatan per hari. Perintah terjadwal `attendance:mark-absent` mengisi status Alpa bagi murid aktif yang belum tercatat pada pukul 23:59.

Guru melakukan absen masuk dan pulang menggunakan lokasi serta selfie. Selfie dan lampiran disimpan pada disk privat; selfie hanya dapat dilihat admin atau guru pemilik. Penanda absensi dapat dilihat dan diputuskan admin pada antrean tinjauan; keputusan dicatat di audit aktivitas. Guru dapat melihat rekap bulanan kelas yang diampu dan mengoreksi status dengan alasan wajib. Orang tua dapat melihat rekap kehadiran anak yang tertaut. Administrator dapat mengatur absensi dan mengunduh rekap bulanan guru sebagai CSV UTF-8 yang kompatibel dengan Excel.

Fitur absensi memerlukan migrasi `2026_10_12_120000_create_attendance_tables.php` dan `2026_10_13_180000_strengthen_attendance_and_create_challenges.php`. Penjadwal Laravel harus berjalan untuk penandaan Alpa, penutupan tagihan kedaluwarsa, serta pembersihan tantangan lama. Berkas lampiran dan selfie tidak boleh dipublikasikan melalui tautan penyimpanan publik. GPS browser hanya berfungsi di HTTPS atau `localhost`; jika izin lokasi ditolak, izinkan lokasi melalui pengaturan browser. Masa retensi data lokasi mengikuti ketentuan administrasi sekolah yang dirujuk pada Kebijakan Privasi.

## Penerimaan Peserta Didik Baru

Informasi PPDB publik tersedia di `/ppdb`. Administrator dapat mengatur status buka/tutup, jadwal gelombang, alur, dan biaya dari menu PPDB. Calon murid membuat akun pendaftar, menyimpan data bertahap sebagai draf, mengunggah berkas ke penyimpanan privat, dan memantau hasil verifikasi serta keputusan akhir. Pendaftar yang diterima berubah menjadi murid aktif setelah admin memilih kelas sesuai jenjang.

Untuk mengaktifkan fitur, jalankan migrasi baru `2026_10_12_110000_create_ppdb_tables.php` melalui koneksi sekolah setelah meninjau konfigurasi lingkungan. Berkas pendaftaran menggunakan disk `local` privat; jangan membuat tautan publik ke direktori penyimpanan ini. Ekspor data tersedia sebagai CSV UTF-8 yang dapat dibuka di Excel, dan bukti pendaftaran disediakan sebagai PDF.

## Profil Publik Sekolah dan Data Demo

Halaman publik profil sekolah dikelola dari menu admin **Profil Sekolah**, **Fasilitas**, dan **Galeri**. Isi data profil sebelum publikasi; nilai awal ditandai `[Isi dari panel admin]`. Unggahan galeri dan foto fasilitas menerima JPG, PNG, atau WEBP hingga 5 MB dan memakai nama penyimpanan acak. Jalankan migrasi baru terlebih dahulu, lalu buat tautan penyimpanan publik untuk foto bila tautan belum tersedia:

```bash
php artisan migrate
php artisan storage:link
```

Data uji lokal tersedia melalui seeder terpisah dan tidak dipanggil oleh `DatabaseSeeder`. Seeder ini menolak semua lingkungan selain `local`:

```bash
php artisan db:seed --class="Database\Seeders\DemoDataSeeder"
```

Untuk menggunakan kata sandi sendiri, isi `DEMO_PASSWORD` di environment lokal sebelum menjalankan seeder. Jika kosong, seeder membuat kata sandi acak dan menampilkannya sekali di keluaran perintah. Seeder hanya membuat data fiktif dengan alamat `example.test`; jangan gunakan akun demo di luar lingkungan lokal.

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
