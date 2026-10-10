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
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-white transition hover:bg-muhammadiyah-hijau focus:outline-none focus:ring-2 focus:ring-muhammadiyah-kuning sm:px-4">Login</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-muhammadiyah-kuning px-3 py-2 text-sm font-bold text-muhammadiyah-hijau-tua transition hover:bg-muhammadiyah-kuning/90 focus:outline-none focus:ring-2 focus:ring-white sm:px-4">Daftar Sekarang</a>
                </div>
            </nav>
        </header>

        <main>
            <section class="bg-muhammadiyah-hijau-muda">
                <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-2 lg:px-8 lg:py-28">
                    <div class="max-w-2xl">
                        <p class="mb-4 inline-flex rounded-full bg-white px-4 py-2 text-sm font-semibold text-muhammadiyah-hijau-tua shadow-sm">
                            Pendidikan Islami, unggul, dan berkemajuan
                        </p>
                        <h1 class="text-4xl font-bold leading-tight text-muhammadiyah-hijau-tua sm:text-5xl lg:text-6xl">
                            Selamat Datang di<br class="hidden sm:block"> SMP SMA Muhammadiyah Subang
                        </h1>
                        <p class="mt-6 max-w-xl text-lg leading-8 text-gray-700">
                            Wujudkan generasi berakhlak mulia, berprestasi, dan siap menghadapi masa depan bersama lingkungan belajar yang inspiratif.
                        </p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-lg bg-muhammadiyah-hijau px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-muhammadiyah-hijau-tua focus:outline-none focus:ring-2 focus:ring-muhammadiyah-kuning focus:ring-offset-2">
                                Login
                            </a>
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-lg bg-muhammadiyah-kuning px-6 py-3 font-bold text-muhammadiyah-hijau-tua shadow-sm transition hover:bg-muhammadiyah-kuning/90 focus:outline-none focus:ring-2 focus:ring-muhammadiyah-hijau focus:ring-offset-2">
                                Daftar Sekarang
                            </a>
                        </div>
                    </div>

                    <div class="mx-auto flex w-full max-w-md items-center justify-center rounded-3xl bg-white p-8 shadow-xl sm:p-12">
                        <div class="flex h-56 w-56 items-center justify-center rounded-full border-[18px] border-muhammadiyah-hijau-muda bg-muhammadiyah-hijau-tua shadow-inner sm:h-72 sm:w-72">
                            <span class="flex h-36 w-36 items-center justify-center rounded-full bg-muhammadiyah-kuning text-7xl font-bold text-muhammadiyah-hijau-tua sm:h-44 sm:w-44 sm:text-8xl" aria-label="Lambang Muhammadiyah">M</span>
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
    </body>
</html>
