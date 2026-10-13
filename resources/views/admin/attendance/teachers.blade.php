<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Rekap Absensi Guru</h1></x-slot>

    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('admin.attendance.teachers') }}" class="flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm">
            <div><label for="month" class="block font-semibold">Bulan</label><input id="month" name="month" type="month" value="{{ $month }}" class="mt-1 min-h-11 rounded-lg border-gray-300"></div>
            <button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white">Tampilkan</button>
            <a href="{{ route('admin.attendance.teachers.export', ['month' => $month]) }}" class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 px-5 font-semibold">Ekspor Excel (CSV)</a>
            <a href="{{ route('admin.attendance.settings') }}" class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 px-5 font-semibold">Pengaturan absensi</a>
        </form>
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-700"><tr><th class="px-4 py-3">Guru</th><th class="px-4 py-3">NIP</th><th class="px-4 py-3">Hari tercatat</th><th class="px-4 py-3">Rincian harian</th><th class="px-4 py-3">Deteksi</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($teachers as $teacher)
                            <tr class="align-top">
                                <td class="px-4 py-3 font-semibold">{{ $teacher->user->name }}</td>
                                <td class="px-4 py-3">{{ $teacher->employee_number }}</td>
                                <td class="px-4 py-3">{{ $teacher->attendances->count() }} hari</td>
                                <td class="px-4 py-3">
                                    @forelse ($teacher->attendances as $record)
                                        <div class="mb-2">
                                            <p class="font-semibold">{{ $record->attendance_date->format('d/m') }}: Masuk {{ $record->check_in_at?->format('H:i') ?? '—' }} / Pulang {{ $record->check_out_at?->format('H:i') ?? '—' }}</p>
                                            @if ($record->check_in_at)
                                                <p class="text-xs text-gray-600">Lokasi masuk {{ $record->check_in_latitude }}, {{ $record->check_in_longitude }} · akurasi {{ $record->check_in_accuracy_meters }} m · IP {{ $record->check_in_ip_address }}</p>
                                                @if ($record->check_in_selfie_path)<a class="text-xs font-semibold text-muhammadiyah-hijau-tua underline" href="{{ route('admin.attendance.teachers.selfie', [$record, 'masuk']) }}">Unduh selfie masuk</a>@endif
                                            @endif
                                            @if ($record->check_out_at)
                                                <p class="text-xs text-gray-600">Lokasi pulang {{ $record->check_out_latitude }}, {{ $record->check_out_longitude }} · akurasi {{ $record->check_out_accuracy_meters }} m · IP {{ $record->check_out_ip_address }}</p>
                                                @if ($record->check_out_selfie_path)<a class="text-xs font-semibold text-muhammadiyah-hijau-tua underline" href="{{ route('admin.attendance.teachers.selfie', [$record, 'pulang']) }}">Unduh selfie pulang</a>@endif
                                            @endif
                                        </div>
                                    @empty
                                        <span class="text-gray-600">Belum ada absensi bulan ini.</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-3">
                                    @php($flaggedRecords = $teacher->attendances->where('is_suspicious', true))
                                    @forelse ($flaggedRecords as $record)
                                        <p class="font-semibold text-amber-800">Perlu ditinjau ({{ $record->attendance_date->format('d/m') }})</p>
                                        <p class="mb-2 text-xs text-gray-600">{{ implode('; ', $record->suspicious_reasons ?? []) }}</p>
                                    @empty
                                        <span class="text-gray-600">Tidak ada tanda terdeteksi</span>
                                    @endforelse
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-600">Belum ada akun guru.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <p class="text-sm text-gray-600">Deteksi GPS palsu dan penggunaan perangkat bersama hanya perkiraan untuk membantu peninjauan, bukan bukti pasti.</p>
    </main>
</x-app-layout>
