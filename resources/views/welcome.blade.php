@php
    $placeholder = '[Isi dari panel admin]';
    $schoolName = filled($schoolProfile?->name) && $schoolProfile->name !== $placeholder
        ? $schoolProfile->name
        : 'SMP SMA Muhammadiyah Subang';
    $schoolTagline = filled($schoolProfile?->tagline) && $schoolProfile->tagline !== $placeholder
        ? $schoolProfile->tagline
        : 'Pendidikan Islami, unggul, dan berkemajuan';
    $welcomeMessage = filled($schoolProfile?->welcome_message) && $schoolProfile->welcome_message !== $placeholder
        ? $schoolProfile->welcome_message
        : 'Selamat datang di lingkungan pendidikan yang menumbuhkan keimanan, ilmu, dan karakter. Kami berkomitmen mendampingi setiap peserta didik untuk belajar dan berkembang.';
@endphp
<x-layouts.public :title="$schoolName" :description="$schoolTagline">
    <section class="welcome-hero welcome-hero--with-image" style="--hero-image: url('{{ asset('images/latar-beranda.jpg') }}')">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-28">
            <div class="max-w-3xl">
                <p class="mb-4 inline-flex rounded-full bg-white px-4 py-2 text-sm font-semibold text-muhammadiyah-hijau-tua shadow-sm">
                    {{ $schoolTagline }}
                </p>
                <h1 class="text-4xl font-bold leading-tight text-white sm:text-5xl lg:text-6xl">
                    {{ $schoolName }}
                </h1>
                <p class="mt-6 max-w-2xl whitespace-pre-line text-lg leading-8 text-white">{{ $welcomeMessage }}</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('ppdb.information') }}" class="inline-flex min-h-12 items-center justify-center rounded-lg bg-muhammadiyah-kuning px-6 py-3 font-bold text-muhammadiyah-hijau-tua shadow-sm hover:bg-white">Daftar PPDB</a>
                    <a href="{{ route('public.contact') }}" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-white px-6 py-3 font-semibold text-white hover:bg-white/10">Hubungi Sekolah</a>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-5 px-4 py-10 sm:px-6 md:grid-cols-2 lg:px-8">
        @foreach ([['SMP', route('public.level.smp')], ['SMA', route('public.level.sma')]] as [$level, $href])
            <article class="rounded-2xl border border-green-100 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-muhammadiyah-hijau">{{ $level }}</p>
                <h2 class="mt-2 text-2xl font-bold text-muhammadiyah-hijau-tua">Informasi Jenjang {{ $level }}</h2>
                <p class="mt-3 whitespace-pre-line leading-7 text-gray-700">{{ ($level === 'SMP' ? $schoolProfile?->smp_description : $schoolProfile?->sma_description) ?? '[Isi dari panel admin]' }}</p>
                <a href="{{ $href }}" class="mt-4 inline-flex min-h-11 items-center font-semibold text-muhammadiyah-hijau-tua underline">Lihat informasi {{ $level }}</a>
            </article>
        @endforeach
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="font-semibold text-muhammadiyah-hijau">Informasi terbaru</p>
                <h2 class="mt-1 text-2xl font-bold text-muhammadiyah-hijau-tua">Pengumuman dan berita</h2>
            </div>
            <a href="{{ route('public.announcements') }}" class="inline-flex min-h-11 items-center font-semibold text-muhammadiyah-hijau-tua underline">Semua berita</a>
        </div>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($announcements as $announcement)
                <article class="rounded-xl bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-600">{{ $announcement->published_at->format('d/m/Y') }}</p>
                    <h3 class="mt-2 text-lg font-bold text-muhammadiyah-hijau-tua">
                        <a href="{{ route('public.announcement', ['announcement' => $announcement->slug]) }}">{{ $announcement->title }}</a>
                    </h3>
                    <p class="mt-2 line-clamp-3 whitespace-pre-line leading-6 text-gray-700">{{ \Illuminate\Support\Str::limit($announcement->body, 180) }}</p>
                </article>
            @empty
                <p class="rounded-xl bg-white p-5 text-gray-700">Belum ada pengumuman yang ditayangkan.</p>
            @endforelse
        </div>
        @if ($facilities->isNotEmpty())
            <div class="mt-10">
                <h2 class="text-2xl font-bold text-muhammadiyah-hijau-tua">Fasilitas sekolah</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    @foreach ($facilities as $facility)
                        <article class="overflow-hidden rounded-xl bg-white shadow-sm">
                            @if ($facility->photo_path)<img src="{{ asset('storage/'.$facility->photo_path) }}" alt="{{ $facility->name }}" class="h-48 w-full object-cover">@endif
                            <div class="p-5"><h3 class="font-bold text-muhammadiyah-hijau-tua">{{ $facility->name }}</h3><p class="mt-2 whitespace-pre-line text-gray-700">{{ $facility->description }}</p></div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</x-layouts.public>
