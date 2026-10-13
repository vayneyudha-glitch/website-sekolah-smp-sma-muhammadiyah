<x-layouts.public>
    <section class="mx-auto max-w-5xl space-y-8 px-4 py-10 sm:px-6 lg:px-8">
        <header class="rounded-2xl bg-white p-6 shadow-sm sm:p-10">
            <p class="font-semibold uppercase tracking-wide text-muhammadiyah-hijau">Penerimaan Peserta Didik Baru</p>
            <h1 class="mt-2 text-3xl font-bold text-muhammadiyah-hijau-tua sm:text-4xl">PPDB SMP SMA Muhammadiyah Subang</h1>
            <p class="mt-4 leading-7 text-gray-700">Informasi jadwal, alur, dan biaya pendaftaran tahun berjalan.</p>
            @if ($settings?->starts_on || $settings?->ends_on)
                <p class="mt-3 font-semibold text-gray-800">
                    Periode: {{ $settings?->starts_on?->format('d/m/Y') ?? 'belum ditentukan' }}
                    — {{ $settings?->ends_on?->format('d/m/Y') ?? 'belum ditentukan' }}
                </p>
            @endif
            @if ($settings?->is_open)
                <a href="{{ route('ppdb.register.create') }}" class="mt-6 inline-flex min-h-12 items-center justify-center rounded-lg bg-muhammadiyah-hijau px-6 py-3 font-bold text-white hover:bg-muhammadiyah-hijau-tua">Daftar Sekarang</a>
            @else
                <p class="mt-6 rounded-lg bg-amber-50 p-4 font-semibold text-amber-900">Pendaftaran PPDB sedang ditutup.</p>
            @endif
        </header>

        <div class="grid gap-5 md:grid-cols-3">
            <article class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-muhammadiyah-hijau-tua">Jadwal gelombang</h2>
                <div class="mt-3 whitespace-pre-line leading-7 text-gray-700">{{ $settings?->wave_schedule ?? 'Informasi jadwal belum tersedia.' }}</div>
            </article>
            <article class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-muhammadiyah-hijau-tua">Alur pendaftaran</h2>
                <div class="mt-3 whitespace-pre-line leading-7 text-gray-700">{{ $settings?->registration_flow ?? 'Informasi alur belum tersedia.' }}</div>
            </article>
            <article class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-muhammadiyah-hijau-tua">Biaya</h2>
                <div class="mt-3 whitespace-pre-line leading-7 text-gray-700">{{ $settings?->costs ?? 'Informasi biaya belum tersedia.' }}</div>
            </article>
        </div>
    </section>
</x-layouts.public>
