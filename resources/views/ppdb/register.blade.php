<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Buat akun PPDB — SMP SMA Muhammadiyah Subang</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-muhammadiyah-hijau-muda px-4 py-10 font-sans text-gray-800">
        <main class="mx-auto max-w-xl rounded-2xl bg-white p-6 shadow-sm sm:p-9">
            <a href="{{ route('ppdb.information') }}" class="font-semibold text-muhammadiyah-hijau-tua underline">Kembali ke informasi PPDB</a>
            <h1 class="mt-5 text-2xl font-bold text-muhammadiyah-hijau-tua">Buat akun pendaftar</h1>
            <p class="mt-2 text-gray-600">Akun ini digunakan untuk menyimpan draf, melengkapi berkas, dan memantau hasil seleksi.</p>
            @if ($errors->any())
                <div role="alert" class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                    <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ route('ppdb.register.store') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="name" class="block font-semibold">Nama calon murid</label>
                    <input id="name" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" class="mt-1 min-h-11 w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label for="email" class="block font-semibold">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1 min-h-11 w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label for="password" class="block font-semibold">Kata sandi</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password" class="mt-1 min-h-11 w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label for="password_confirmation" class="block font-semibold">Ulangi kata sandi</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="mt-1 min-h-11 w-full rounded-lg border-gray-300">
                </div>
                <label class="flex items-start gap-3 text-sm text-gray-700">
                    <input type="checkbox" name="privacy_consent" value="1" required @checked(old('privacy_consent')) class="mt-1 rounded border-gray-300 text-muhammadiyah-hijau focus:ring-muhammadiyah-hijau">
                    <span>Saya telah membaca dan menyetujui <a href="{{ route('legal.privacy') }}" class="font-semibold text-muhammadiyah-hijau-tua underline">Kebijakan Privasi</a> untuk pemrosesan data pendaftaran PPDB.</span>
                </label>
                <button class="inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-muhammadiyah-hijau px-5 font-bold text-white hover:bg-muhammadiyah-hijau-tua">Buat akun dan lanjutkan</button>
            </form>
        </main>
    </body>
</html>
