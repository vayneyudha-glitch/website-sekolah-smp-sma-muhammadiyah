<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-muhammadiyah-hijau-tua">Ganti kata sandi sementara</h1>
        <p class="mt-1 text-sm text-gray-600">Buat kata sandi baru untuk melanjutkan ke portal sekolah.</p>
    </div>

    <form method="POST" action="{{ route('password.first-change.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="current_password" value="Kata sandi sementara" />
            <x-text-input id="current_password" class="mt-1 block w-full" type="password" name="current_password" required autocomplete="current-password" autofocus />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Kata sandi baru" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Ulangi kata sandi baru" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <x-primary-button class="w-full justify-center">Simpan kata sandi baru</x-primary-button>
    </form>
</x-guest-layout>
