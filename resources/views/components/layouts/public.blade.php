@props([
    'title' => null,
    'description' => 'Informasi resmi sekolah. Lengkapi konten melalui panel admin.',
])
@php
    $placeholder = '[Isi dari panel admin]';
    $schoolName = filled($schoolProfile?->name) && $schoolProfile->name !== $placeholder
        ? $schoolProfile->name
        : 'SMP SMA Muhammadiyah Subang';
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ $description }}">
        <meta property="og:type" content="website">
        <meta property="og:locale" content="id_ID">
        <meta property="og:site_name" content="{{ $schoolName }}">
        <meta property="og:title" content="{{ $title ?? $schoolName }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('images/logo.png') }}">
        <title>{{ $title ?? $schoolName }} — {{ $schoolName }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-muhammadiyah-hijau-muda font-sans text-gray-800 antialiased">
        <header class="bg-muhammadiyah-hijau-tua text-white shadow">
            <nav x-data="{ open: false }" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" aria-label="Navigasi utama">
                <div class="flex min-h-16 items-center justify-between gap-3 py-3">
                    <a href="{{ route('public.home') }}" class="flex max-w-[70%] shrink-0 items-center gap-2 font-bold leading-tight">
                        <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-9 shrink-0 object-contain">
                        <span>{{ $schoolName }}</span>
                    </a>
                    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="public-menu" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-white/40 px-3 font-semibold lg:hidden">
                        <span class="sr-only">Buka navigasi</span>
                        <span aria-hidden="true">☰</span>
                    </button>
                    <div class="hidden flex-wrap items-center justify-end gap-1 text-sm font-semibold lg:flex">
                        <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('public.about') }}">Tentang</a>
                        <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('public.vision-mission') }}">Visi Misi</a>
                        <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('public.history') }}">Sejarah</a>
                        <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('public.level.smp') }}">SMP</a>
                        <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('public.level.sma') }}">SMA</a>
                        <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('public.facilities') }}">Fasilitas</a>
                        <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('public.gallery') }}">Galeri</a>
                        <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('public.announcements') }}">Berita</a>
                        <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('public.teachers') }}">Guru</a>
                        <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('public.contact') }}">Kontak</a>
                        <a class="rounded-lg bg-muhammadiyah-kuning px-3 py-2 text-muhammadiyah-hijau-tua hover:bg-white" href="{{ route('ppdb.information') }}">Daftar PPDB</a>
                        <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('login') }}">Login</a>
                    </div>
                </div>
                <div id="public-menu" x-cloak x-show="open" class="space-y-1 border-t border-white/20 pb-4 pt-2 lg:hidden">
                    @foreach ([
                        ['Tentang Sekolah', route('public.about')],
                        ['Visi dan Misi', route('public.vision-mission')],
                        ['Sejarah', route('public.history')],
                        ['Jenjang SMP', route('public.level.smp')],
                        ['Jenjang SMA', route('public.level.sma')],
                        ['Fasilitas', route('public.facilities')],
                        ['Galeri', route('public.gallery')],
                        ['Berita', route('public.announcements')],
                        ['Daftar Guru', route('public.teachers')],
                        ['Kontak', route('public.contact')],
                        ['Daftar PPDB', route('ppdb.information')],
                        ['Login', route('login')],
                    ] as [$label, $href])
                        <a class="block min-h-11 rounded-lg px-3 py-3 hover:bg-muhammadiyah-hijau" href="{{ $href }}">{{ $label }}</a>
                    @endforeach
                </div>
            </nav>
        </header>
        <main>{{ $slot }}</main>
        <footer class="mt-12 flex flex-col gap-2 bg-muhammadiyah-hijau-tua px-4 py-5 text-center text-sm text-white sm:flex-row sm:justify-center sm:gap-6">
            <span>&copy; {{ date('Y') }} {{ $schoolName }}</span>
            <a href="{{ route('legal.privacy') }}" class="underline">Kebijakan Privasi</a>
            <a href="{{ route('legal.terms') }}" class="underline">Syarat Penggunaan</a>
        </footer>
        <x-whatsapp-button :number="$schoolProfile?->whatsapp" />
    </body>
</html>
