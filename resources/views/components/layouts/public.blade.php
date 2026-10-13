<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Informasi Sekolah' }} — SMP SMA Muhammadiyah Subang</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-muhammadiyah-hijau-muda font-sans text-gray-800 antialiased">
        <header class="bg-muhammadiyah-hijau-tua text-white shadow">
            <nav class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8" aria-label="Navigasi utama">
                <a href="/" class="font-bold leading-tight">SMP SMA Muhammadiyah Subang</a>
                <div class="flex flex-wrap items-center justify-end gap-2 text-sm font-semibold">
                    <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('public.teachers') }}">Daftar Guru</a>
                    <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('public.announcements') }}">Berita</a>
                    <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('ppdb.information') }}">PPDB</a>
                    <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('legal.privacy') }}">Privasi</a>
                    <a class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau" href="{{ route('legal.terms') }}">Syarat</a>
                    <a class="rounded-lg bg-white px-3 py-2 text-muhammadiyah-hijau-tua hover:bg-muhammadiyah-kuning" href="{{ route('login') }}">Login</a>
                </div>
            </nav>
        </header>
        <main>{{ $slot }}</main>
        <footer class="mt-12 flex flex-col gap-2 bg-muhammadiyah-hijau-tua px-4 py-5 text-center text-sm text-white sm:flex-row sm:justify-center sm:gap-6">
            <span>&copy; {{ date('Y') }} SMP SMA Muhammadiyah Subang</span>
            <a href="{{ route('legal.privacy') }}" class="underline">Kebijakan Privasi</a>
            <a href="{{ route('legal.terms') }}" class="underline">Syarat Penggunaan</a>
        </footer>
        <x-whatsapp-button />
    </body>
</html>
