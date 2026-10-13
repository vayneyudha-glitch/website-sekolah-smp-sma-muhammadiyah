<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-bold text-white">{{ $title }}</h1>
            <a href="{{ route('admin.data.create', ['resource' => $resource]) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-muhammadiyah-kuning px-5 py-2 font-bold text-muhammadiyah-hijau-tua hover:bg-white">
                Tambah data
            </a>
        </div>
    </x-slot>

    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))
            <div role="status" class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-900">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">{{ session('error') }}</div>
        @endif
        @if ($temporaryPassword)
            <section class="rounded-xl border-2 border-amber-400 bg-amber-50 p-5" aria-labelledby="temporary-password-title">
                <h2 id="temporary-password-title" class="font-bold text-amber-950">Akun berhasil dibuat — simpan kata sandi sementara ini</h2>
                <p class="mt-2 text-sm text-amber-950">Nama akun: <strong>{{ $temporaryAccount }}</strong></p>
                <p class="mt-1 break-all rounded bg-white p-3 font-mono text-lg font-bold text-gray-900">{{ $temporaryPassword }}</p>
                <p class="mt-2 text-sm text-amber-950">Kata sandi hanya ditampilkan sekali dan wajib diganti saat login pertama.</p>
            </section>
        @endif

        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <ul class="divide-y divide-gray-100">
                @forelse ($records as $record)
                    <li class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <p class="min-w-0 break-words font-medium text-gray-900">{{ $summary($record) }}</p>
                        <div class="flex shrink-0 gap-2">
                            <a href="{{ route('admin.data.edit', ['resource' => $resource, 'record' => $record->getKey()]) }}" class="inline-flex min-h-10 items-center rounded-lg border border-muhammadiyah-hijau px-4 py-2 text-sm font-semibold text-muhammadiyah-hijau-tua hover:bg-muhammadiyah-hijau-muda">Ubah</a>
                            @if ($resource !== 'accounts')
                                <form method="POST" action="{{ route('admin.data.destroy', ['resource' => $resource, 'record' => $record->getKey()]) }}" onsubmit="return confirm('Hapus data ini? Tindakan ini tidak dapat dibatalkan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex min-h-10 items-center rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="p-8 text-center text-gray-600">Belum ada {{ strtolower($title) }}. Pilih “Tambah data” untuk memulai.</li>
                @endforelse
            </ul>
            @if ($records->hasPages())
                <div class="border-t border-gray-100 px-4 py-3">{{ $records->links() }}</div>
            @endif
        </section>
        <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center font-semibold text-muhammadiyah-hijau-tua underline">Kembali ke dashboard admin</a>
    </main>
</x-app-layout>
