<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-white">Rekap Absensi Murid — {{ $schoolClass->school_level }} {{ $schoolClass->grade }} {{ $schoolClass->name }}</h1>
    </x-slot>

    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm">
            <div><label for="month" class="block font-semibold">Bulan</label><input id="month" name="month" type="month" value="{{ $month }}" class="mt-1 min-h-11 rounded-lg border-gray-300"></div>
            <button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white">Tampilkan rekap</button>
        </form>
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3">Murid</th><th class="px-4 py-3">Riwayat bulan {{ $month }}</th><th class="px-4 py-3">Koreksi</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($students as $student)
                            <tr class="align-top">
                                <td class="px-4 py-4 font-semibold">{{ $student->user->name }}<span class="mt-1 block text-xs font-normal text-gray-600">NIS {{ $student->nis }}</span></td>
                                <td class="px-4 py-4">
                                    @forelse ($student->attendances as $record)
                                        <p class="mb-1">{{ $record->attendance_date->format('d/m') }}: <span class="font-semibold">{{ ucfirst($record->status) }}</span>@if ($record->edited_at) <span class="text-xs text-gray-500">(dikoreksi)</span>@endif</p>
                                    @empty
                                        <span class="text-gray-500">Belum ada absensi bulan ini.</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-4">
                                    <details>
                                        <summary class="cursor-pointer font-semibold text-muhammadiyah-hijau-tua">Koreksi absensi</summary>
                                        <form method="POST" action="{{ route('guru.classes.attendance.correct', [$schoolClass, $student]) }}" class="mt-3 min-w-64 space-y-3">
                                            @csrf @method('PUT')
                                            <div><label for="attendance-date-{{ $student->id }}" class="block font-medium">Tanggal</label><input id="attendance-date-{{ $student->id }}" name="attendance_date" type="date" max="{{ today()->toDateString() }}" required value="{{ $student->attendances->last()?->attendance_date->toDateString() ?? today()->toDateString() }}" class="mt-1 min-h-10 w-full rounded-lg border-gray-300"></div>
                                            <div><label for="attendance-status-{{ $student->id }}" class="block font-medium">Status</label><select id="attendance-status-{{ $student->id }}" name="status" required class="mt-1 min-h-10 w-full rounded-lg border-gray-300"><option value="hadir">Hadir</option><option value="izin">Izin</option><option value="sakit">Sakit</option><option value="alpa">Alpa</option></select></div>
                                            <div><label for="attendance-reason-{{ $student->id }}" class="block font-medium">Alasan koreksi (wajib)</label><textarea id="attendance-reason-{{ $student->id }}" name="reason" required maxlength="2000" rows="2" class="mt-1 w-full rounded-lg border-gray-300"></textarea></div>
                                            <button class="min-h-10 rounded-lg bg-muhammadiyah-hijau px-4 font-semibold text-white">Simpan koreksi</button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-600">Belum ada murid aktif di kelas ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</x-app-layout>
