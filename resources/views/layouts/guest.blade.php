<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>SMP SMA Muhammadiyah Subang</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-800 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 py-8 sm:py-12 bg-muhammadiyah-hijau-muda">
            <div class="mb-6 text-center">
                <a href="/">
                    <x-application-logo class="mx-auto h-16 w-16 text-2xl" />
                    <span class="mt-3 block text-lg font-bold text-muhammadiyah-hijau-tua">SMP SMA Muhammadiyah Subang</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md px-6 py-6 bg-white shadow-lg overflow-hidden rounded-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
