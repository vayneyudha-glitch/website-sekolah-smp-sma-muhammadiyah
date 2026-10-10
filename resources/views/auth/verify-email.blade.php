<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Terima kasih telah mendaftar. Sebelum melanjutkan, verifikasi alamat email Anda melalui tautan yang telah kami kirimkan. Jika belum menerima email, kami dapat mengirimkannya kembali.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-muhammadiyah-hijau">
            Tautan verifikasi baru telah dikirim ke alamat email yang Anda daftarkan.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    Kirim ulang email verifikasi
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-muhammadiyah-hijau-tua hover:text-muhammadiyah-hijau rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-muhammadiyah-hijau">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
