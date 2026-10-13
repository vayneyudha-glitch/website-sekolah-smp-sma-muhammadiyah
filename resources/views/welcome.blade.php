<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>SMP SMA Muhammadiyah Subang</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white font-sans text-gray-800 antialiased">
        <header class="bg-muhammadiyah-hijau-tua text-white">
            <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Navigasi utama">
                <a href="/" class="flex items-center gap-3">
                    <x-application-logo />
                    <span class="max-w-[12rem] text-sm font-bold leading-tight sm:max-w-none sm:text-base">SMP SMA Muhammadiyah Subang</span>
                </a>
                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="{{ route('public.teachers') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-white hover:bg-muhammadiyah-hijau">Daftar Guru</a>
                    <a href="{{ route('public.announcements') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-white hover:bg-muhammadiyah-hijau">Berita</a>
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-white transition hover:bg-muhammadiyah-hijau focus:outline-none focus:ring-2 focus:ring-muhammadiyah-kuning sm:px-4">Login</a>
                    <a href="{{ route('ppdb.information') }}" class="rounded-lg bg-muhammadiyah-kuning px-3 py-2 text-sm font-bold text-muhammadiyah-hijau-tua transition hover:bg-muhammadiyah-kuning/90 focus:outline-none focus:ring-2 focus:ring-white sm:px-4">PPDB</a>
                </div>
            </nav>
        </header>

        <main>
            <section class="welcome-hero @if (file_exists(public_path('images/latar-beranda.jpg'))) welcome-hero--with-image @endif" @if (file_exists(public_path('images/latar-beranda.jpg'))) style="--hero-image: url('{{ asset('images/latar-beranda.jpg') }}')" @endif>
                <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-28">
                    <div class="max-w-[640px]">
                        <p class="mb-4 inline-flex rounded-full bg-white px-4 py-2 text-sm font-semibold text-muhammadiyah-hijau-tua shadow-sm">
                            Pendidikan Islami, unggul, dan berkemajuan
                        </p>
                        <h1 class="text-4xl font-bold leading-tight text-white sm:text-5xl lg:text-6xl">
                            Selamat Datang di<br class="hidden sm:block"> SMP SMA Muhammadiyah Subang
                        </h1>
                        <p class="mt-6 max-w-xl text-lg leading-8 text-white/90">
                            Wujudkan generasi berakhlak mulia, berprestasi, dan siap menghadapi masa depan bersama lingkungan belajar yang inspiratif.
                        </p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-lg bg-muhammadiyah-hijau px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-muhammadiyah-hijau-tua focus:outline-none focus:ring-2 focus:ring-muhammadiyah-kuning focus:ring-offset-2">
                                Login
                            </a>
                            <a href="{{ route('ppdb.information') }}" class="inline-flex items-center justify-center rounded-lg bg-muhammadiyah-kuning px-6 py-3 font-bold text-muhammadiyah-hijau-tua shadow-sm transition hover:bg-muhammadiyah-kuning/90 focus:outline-none focus:ring-2 focus:ring-muhammadiyah-hijau focus:ring-offset-2">
                                Daftar Sekarang
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-4 py-12 text-center sm:px-6 sm:py-16 lg:px-8">
                <h2 class="text-2xl font-bold text-muhammadiyah-hijau-tua sm:text-3xl">Belajar dan bertumbuh bersama</h2>
                <p class="mx-auto mt-4 max-w-2xl leading-7 text-gray-600">
                    Portal sekolah SMP SMA Muhammadiyah Subang membantu warga sekolah mengakses layanan dan informasi dengan mudah.
                </p>
            </section>
        </main>

        <footer class="bg-muhammadiyah-hijau-tua px-4 py-5 text-center text-sm text-white sm:px-6">
            &copy; {{ date('Y') }} SMP SMA Muhammadiyah Subang. Semua hak dilindungi.
        </footer>
        <x-whatsapp-button />
    </body>
</html>
