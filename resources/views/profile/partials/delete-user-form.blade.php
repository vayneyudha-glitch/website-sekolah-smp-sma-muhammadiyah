<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-800">
            Hapus akun
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Setelah akun dihapus, seluruh data dan sumber daya Anda akan dihapus secara permanen. Unduh terlebih dahulu informasi yang masih ingin Anda simpan.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Hapus akun</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-800">
                Apakah Anda yakin ingin menghapus akun?
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Setelah akun dihapus, seluruh data Anda akan dihapus secara permanen. Masukkan kata sandi untuk mengonfirmasi penghapusan akun.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Kata sandi" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Kata sandi"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Hapus akun
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
