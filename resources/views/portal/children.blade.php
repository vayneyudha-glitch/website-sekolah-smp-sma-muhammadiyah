<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-white">Data Anak</h1>
    </x-slot>

    <main class="mx-auto max-w-4xl space-y-4 px-4 py-6 sm:px-6 lg:px-8">
        <p class="text-gray-700">Hanya data anak yang sudah ditautkan oleh admin yang ditampilkan di sini.</p>
        @forelse ($children as $child)
            @if ($child->studentProfile)
                <section class="rounded-xl bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-bold text-muhammadiyah-hijau-tua">{{ $child->name }}</h2>
                    <p class="mt-2 text-gray-700">NIS: <strong>{{ $child->studentProfile->nis }}</strong></p>
                    <p class="mt-1 text-gray-700">NISN: <strong>{{ $child->studentProfile->nisn ?: 'Belum diisi' }}</strong></p>
                    <p class="mt-1 text-gray-700">Status: <strong>{{ ucfirst($child->studentProfile->status) }}</strong></p>
                    <p class="mt-1 text-gray-700">Kelas: <strong>{{ $child->studentProfile->schoolClass->school_level }} {{ $child->studentProfile->schoolClass->grade }} {{ $child->studentProfile->schoolClass->name }}</strong></p>
                    <p class="mt-1 text-gray-700">Tahun ajaran: <strong>{{ $child->studentProfile->schoolClass->academicYear->name }}</strong></p>
                    <a href="{{ route('orang_tua.children.assignments', $child) }}" class="mt-4 inline-flex min-h-10 items-center rounded-lg border border-muhammadiyah-hijau px-4 font-semibold text-muhammadiyah-hijau-tua">Lihat nilai tugas</a>
                    <a href="{{ route('orang_tua.children.attendance', $child) }}" class="mt-4 inline-flex min-h-10 items-center rounded-lg bg-muhammadiyah-hijau px-4 font-semibold text-white">Lihat absensi anak</a>
                    <a href="{{ route('orang_tua.children.reports', $child) }}" class="mt-4 inline-flex min-h-10 items-center rounded-lg border border-muhammadiyah-hijau px-4 font-semibold text-muhammadiyah-hijau-tua">Lihat rapor terbit</a>
                    <a href="{{ route('orang_tua.children.excuses.create', $child) }}" class="mt-4 inline-flex min-h-10 items-center rounded-lg border border-amber-400 px-4 font-semibold text-amber-900">Ajukan izin/sakit</a>
                </section>
            @endif
        @empty
            <section class="rounded-xl bg-white p-6 text-center text-gray-700 shadow-sm">Belum ada data anak yang ditautkan ke akun Anda. Silakan hubungi admin sekolah.</section>
        @endforelse
    </main>
</x-app-layout>
