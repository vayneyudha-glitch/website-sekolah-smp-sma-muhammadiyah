<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-bold text-white">Pendaftar PPDB</h1>
            <div class="flex gap-2">
                <a href="{{ route('admin.ppdb.settings') }}" class="min-h-10 rounded-lg border border-white px-4 py-2 text-sm font-semibold text-white hover:bg-muhammadiyah-hijau">Pengaturan PPDB</a>
                <a href="{{ route('admin.ppdb.export', request()->query()) }}" class="min-h-10 rounded-lg bg-muhammadiyah-kuning px-4 py-2 text-sm font-semibold text-muhammadiyah-hijau-tua">Ekspor untuk Excel</a>
            </div>
        </div>
    </x-slot>
    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm">
            <div><label for="school_level" class="block text-sm font-semibold">Jenjang</label><select id="school_level" name="school_level" class="mt-1 min-h-10 rounded-lg border-gray-300"><option value="">Semua</option><option value="SMP" @selected(($filters['school_level'] ?? '') === 'SMP')>SMP</option><option value="SMA" @selected(($filters['school_level'] ?? '') === 'SMA')>SMA</option></select></div>
            <div><label for="status" class="block text-sm font-semibold">Status</label><select id="status" name="status" class="mt-1 min-h-10 rounded-lg border-gray-300"><option value="">Semua</option>@foreach (['draft' => 'Draf', 'submitted' => 'Dikirim', 'accepted' => 'Diterima', 'rejected' => 'Ditolak'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
            <button class="min-h-10 rounded-lg bg-muhammadiyah-hijau px-4 font-semibold text-white">Terapkan filter</button>
        </form>
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3">Nomor / Pendaftar</th><th class="px-4 py-3">Jenjang</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Dikirim</th><th class="px-4 py-3">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($applications as $application)
                            <tr>
                                <td class="px-4 py-3"><strong>{{ $application->registration_number ?? 'Draf' }}</strong><br>{{ $application->student_name }}<br><span class="text-gray-500">{{ $application->user->email }}</span></td>
                                <td class="px-4 py-3">{{ $application->school_level ?? '—' }} {{ $application->major ? '— '.$application->major : '' }}</td>
                                <td class="px-4 py-3">{{ match ($application->status) { 'draft' => 'Draf', 'submitted' => 'Menunggu verifikasi', 'accepted' => 'Diterima', 'rejected' => 'Ditolak', default => $application->status } }}</td>
                                <td class="px-4 py-3">{{ $application->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3"><a href="{{ route('admin.ppdb.show', $application) }}" class="font-semibold text-muhammadiyah-hijau-tua underline">Periksa</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-600">Belum ada pendaftar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-4 py-3">{{ $applications->links() }}</div>
        </section>
    </main>
</x-app-layout>
