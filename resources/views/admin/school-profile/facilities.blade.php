<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-bold text-white">Kelola Fasilitas</h1>
            <a href="{{ route('admin.school-content.facilities.create') }}" class="inline-flex min-h-11 items-center rounded-lg bg-muhammadiyah-kuning px-4 font-bold text-muhammadiyah-hijau-tua">Tambah fasilitas</a>
        </div>
    </x-slot>
    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50"><tr><th scope="col" class="px-4 py-3">Nama</th><th scope="col" class="px-4 py-3">Jenjang</th><th scope="col" class="px-4 py-3">Urutan</th><th scope="col" class="px-4 py-3">Tindakan</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($facilities as $facility)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $facility->name }}</td>
                                <td class="px-4 py-3">{{ $facility->school_level ?: 'Semua jenjang' }}</td>
                                <td class="px-4 py-3">{{ $facility->sort_order }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.school-content.facilities.edit', $facility) }}" class="inline-flex min-h-11 items-center rounded-lg border border-muhammadiyah-hijau px-3 font-semibold text-muhammadiyah-hijau-tua">Ubah</a>
                                        <form method="POST" action="{{ route('admin.school-content.facilities.destroy', $facility) }}" onsubmit="return confirm('Hapus fasilitas ini?');">
                                            @csrf @method('DELETE')
                                            <button class="inline-flex min-h-11 items-center rounded-lg border border-red-200 px-3 font-semibold text-red-700">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-600">Belum ada fasilitas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($facilities->hasPages())<div class="border-t border-gray-100 p-4">{{ $facilities->links() }}</div>@endif
        </section>
        <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center font-semibold text-muhammadiyah-hijau-tua underline">Kembali ke dashboard admin</a>
    </main>
</x-app-layout>
