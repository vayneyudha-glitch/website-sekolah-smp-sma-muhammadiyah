@php
    $placeholder = '[Isi dari panel admin]';
    $schoolTagline = filled($schoolProfile?->tagline) && $schoolProfile->tagline !== $placeholder
        ? $schoolProfile->tagline
        : 'Pendidikan Islami, unggul, dan berkemajuan';
    $welcomeMessage = filled($schoolProfile?->welcome_message) && $schoolProfile->welcome_message !== $placeholder
        ? $schoolProfile->welcome_message
        : 'Selamat datang di lingkungan pendidikan yang menumbuhkan keimanan, ilmu, dan karakter. Kami berkomitmen mendampingi setiap peserta didik untuk belajar dan berkembang.';
@endphp
<x-layouts.public :title="$title" :description="'Informasi '.$title">
    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <article class="rounded-2xl bg-white p-6 shadow-sm sm:p-10">
            <p class="font-semibold text-muhammadiyah-hijau">{{ $schoolTagline }}</p>
            <h1 class="mt-2 text-3xl font-bold text-muhammadiyah-hijau-tua sm:text-4xl">{{ $title }}</h1>

            @if ($page === 'visi-misi')
                <section class="mt-8">
                    <h2 class="text-xl font-bold text-muhammadiyah-hijau-tua">Visi</h2>
                    <p class="mt-3 whitespace-pre-line leading-7 text-gray-700">{{ $schoolProfile->vision ?? '[Isi dari panel admin]' }}</p>
                </section>
                <section class="mt-8">
                    <h2 class="text-xl font-bold text-muhammadiyah-hijau-tua">Misi</h2>
                    <p class="mt-3 whitespace-pre-line leading-7 text-gray-700">{{ $schoolProfile->mission ?? '[Isi dari panel admin]' }}</p>
                </section>
            @elseif ($page === 'jenjang/smp' || $page === 'jenjang/sma')
                <div class="mt-6 whitespace-pre-line break-words leading-8 text-gray-700">{{ $schoolProfile->{$contentField} ?? '[Isi dari panel admin]' }}</div>
                <a href="{{ route('ppdb.information') }}" class="mt-6 inline-flex min-h-12 items-center justify-center rounded-lg bg-muhammadiyah-kuning px-5 font-bold text-muhammadiyah-hijau-tua">Daftar PPDB</a>
            @else
                <div class="mt-6 whitespace-pre-line break-words leading-8 text-gray-700">{{ $contentField === 'welcome_message' ? $welcomeMessage : ($schoolProfile->{$contentField} ?? $placeholder) }}</div>
            @endif
        </article>
    </main>
</x-layouts.public>
