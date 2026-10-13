<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Bantuan</h1></x-slot>

    <main class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
            @if ($role === \App\Models\User::ROLE_ADMIN)
                <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Panduan Admin</h2>
                <ul class="mt-3 list-inside list-disc space-y-2 text-gray-700">
                    <li>Kelola kelas, akun, hubungan orang tua-murid, jadwal, dan konten sekolah melalui menu navigasi.</li>
                    <li>Atur koordinat, radius, ambang akurasi, dan jam absensi melalui menu Absensi.</li>
                    <li>Periksa penanda pada Antrean Tinjauan Absensi. Catat alasan sebelum menyetujui atau menolak; hasilnya masuk audit aktivitas.</li>
                    <li>Gunakan menu Aktivitas untuk memeriksa riwayat perubahan dan menu Ekspor untuk membuat laporan.</li>
                </ul>
            @elseif ($role === \App\Models\User::ROLE_GURU)
                <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Panduan Guru</h2>
                <ul class="mt-3 list-inside list-disc space-y-2 text-gray-700">
                    <li>Buka Absensi untuk mencatat masuk dan pulang. Izinkan lokasi dan siapkan selfie saat diminta.</li>
                    <li>Penanda lokasi adalah perkiraan untuk pemeriksaan, bukan bukti pasti. Hubungi admin bila absensi perlu ditinjau.</li>
                    <li>Menu Kelas Saya menampilkan kelas yang diampu. Rekap murid dan koreksi tersedia untuk kelas yang menjadi tanggung jawab Anda.</li>
                    <li>Gunakan menu Tugas dan Penilaian untuk mengelola tugas serta nilai.</li>
                </ul>
            @elseif ($role === \App\Models\User::ROLE_SISWA)
                <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Panduan Murid</h2>
                <ul class="mt-3 list-inside list-disc space-y-2 text-gray-700">
                    <li>Buka Absensi untuk mencatat Hadir, Izin, atau Sakit. Hadir meminta lokasi; Izin dan Sakit meminta alasan.</li>
                    <li>Periksa jadwal, tugas, nilai, dan tagihan dari menu terkait.</li>
                    <li>Jika data kehadiran atau akun perlu diperbaiki, hubungi guru atau admin sekolah.</li>
                </ul>
            @elseif ($role === \App\Models\User::ROLE_ORANG_TUA)
                <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Panduan Orang Tua</h2>
                <ul class="mt-3 list-inside list-disc space-y-2 text-gray-700">
                    <li>Buka menu Anak untuk melihat informasi, jadwal, dan kehadiran anak yang telah ditautkan ke akun Anda.</li>
                    <li>Periksa tagihan dan status pembayaran dari menu Pembayaran.</li>
                    <li>Hubungi admin sekolah bila anak belum tertaut atau informasi perlu diperbaiki.</li>
                </ul>
            @elseif ($role === \App\Models\User::ROLE_PENDAFTAR)
                <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Panduan Pendaftar PPDB</h2>
                <ul class="mt-3 list-inside list-disc space-y-2 text-gray-700">
                    <li>Buka Pendaftaran Saya untuk melengkapi data dan mengunggah dokumen yang diminta.</li>
                    <li>Periksa kembali data sebelum mengirim pendaftaran. Setelah dikirim, pantau status dan catatan verifikasi di dasbor.</li>
                    <li>Hubungi admin PPDB jika membutuhkan bantuan terkait berkas atau status pendaftaran.</li>
                </ul>
            @else
                <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Panduan akun</h2>
                <p class="mt-3 text-gray-700">Peran akun belum dikenali. Hubungi admin sekolah.</p>
            @endif
            <a href="{{ route('legal.privacy') }}" class="mt-6 inline-flex min-h-11 items-center rounded-lg border border-gray-300 px-4 font-semibold text-muhammadiyah-hijau-tua">Baca Kebijakan Privasi</a>
        </section>
    </main>
</x-app-layout>
