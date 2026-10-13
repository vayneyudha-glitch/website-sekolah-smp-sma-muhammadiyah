<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-white">Catatan Aktivitas</h1>
    </x-slot>

    <main class="mx-auto max-w-7xl space-y-4 px-4 py-6 sm:px-6 lg:px-8">
        <p class="text-gray-700">Riwayat perubahan data penting di portal sekolah.</p>
        @php
            $resourceTitles = [
                'accounts' => 'Pengelolaan Akun',
                'academic-years' => 'Tahun Ajaran',
                'classes' => 'Kelas',
                'subjects' => 'Mata Pelajaran',
                'students' => 'Data Murid',
                'teachers' => 'Data Guru',
                'parents' => 'Akun Orang Tua',
                'parent-links' => 'Hubungan Orang Tua dan Anak',
                'timetables' => 'Jadwal Pelajaran',
                'announcements' => 'Pengumuman dan Berita',
                \App\Models\StudentAttendance::class => 'Kehadiran Murid',
                \App\Models\AttendanceSetting::class => 'Pengaturan Absensi',
                \App\Models\BillType::class => 'Jenis Tagihan',
                \App\Models\StudentInvoice::class => 'Tagihan Murid',
                \App\Models\Payment::class => 'Pembayaran',
            ];
        @endphp
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <ul class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <li class="p-4 sm:px-6">
                        <p class="font-semibold text-gray-900">{{ $log->description }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ $log->user?->name ?? 'Akun sudah tidak aktif' }} · {{ $log->created_at->format('d/m/Y H:i') }} · Data: {{ $resourceTitles[$log->record_type] ?? 'Data sekolah' }} #{{ $log->record_id }}</p>
                    </li>
                @empty
                    <li class="p-8 text-center text-gray-600">Belum ada aktivitas yang tercatat.</li>
                @endforelse
            </ul>
            @if ($logs->hasPages())
                <div class="border-t border-gray-100 px-4 py-3">{{ $logs->links() }}</div>
            @endif
        </section>
    </main>
</x-app-layout>
