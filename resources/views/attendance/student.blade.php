<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Absensi Murid</h1></x-slot>

    <main class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">{{ $student->user->name }}</h2>
            <p class="mt-1 text-gray-600">{{ $student->schoolClass->school_level }} {{ $student->schoolClass->grade }} {{ $student->schoolClass->name }}</p>
            @if ($allowCheckIn)
                @if ($todayAttendance)
                    <p class="mt-4 rounded-lg bg-green-50 p-3 font-semibold text-green-900">Absensi hari ini sudah tercatat: {{ ucfirst($todayAttendance->status) }}.</p>
                    @if ($todayAttendance->review_status !== 'bersih')
                        <div class="mt-3 rounded-lg bg-amber-50 p-4 text-amber-900">
                            <p class="font-semibold">
                                @if ($todayAttendance->review_status === 'perlu_ditinjau')
                                    Absensi ini perlu ditinjau admin.
                                @elseif ($todayAttendance->review_status === 'disetujui')
                                    Tinjauan admin: absensi disetujui.
                                @else
                                    Tinjauan admin: absensi ditolak.
                                @endif
                            </p>
                            <ul class="list-inside list-disc text-sm">@foreach ($todayAttendance->suspicious_reasons ?? [] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
                            @if ($todayAttendance->review_note)<p class="mt-2 text-sm">Catatan admin: {{ $todayAttendance->review_note }}</p>@endif
                        </div>
                    @endif
                @else
                    <form method="POST" action="{{ route('siswa.attendance.store') }}" enctype="multipart/form-data" data-location-form data-challenge-url="{{ route('attendance.challenge') }}" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="attendance_challenge">
                        <div><label for="attendance-status" class="block font-semibold">Status kehadiran</label><select id="attendance-status" name="status" required data-attendance-status class="mt-1 min-h-11 w-full rounded-lg border-gray-300"><option value="hadir">Hadir</option><option value="izin">Izin</option><option value="sakit">Sakit</option></select></div>
                        <label class="flex items-start gap-3 text-sm text-gray-700">
                            <input type="checkbox" name="location_consent" value="1" @checked(old('location_consent')) class="mt-1 rounded border-gray-300 text-muhammadiyah-hijau focus:ring-muhammadiyah-hijau">
                            <span>Saya setuju menggunakan lokasi perangkat untuk memverifikasi absensi hadir. Lokasi tidak diminta untuk status izin atau sakit.</span>
                        </label>
                        <div data-reason-fields class="hidden">
                            <label for="reason" class="block font-semibold">Alasan izin/sakit (wajib)</label>
                            <textarea id="reason" name="reason" rows="3" maxlength="2000" data-reason-input class="mt-1 w-full rounded-lg border-gray-300"></textarea>
                            <label for="attachment" class="mt-3 block font-semibold">Lampiran (opsional, JPG/PNG/PDF maks. 5 MB)</label>
                            <input id="attachment" name="attachment" type="file" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-sm">
                        </div>
                        <input type="hidden" name="latitude" data-latitude>
                        <input type="hidden" name="longitude" data-longitude>
                        <input type="hidden" name="accuracy_meters" data-accuracy>
                        <input type="hidden" name="device_token" data-device-token>
                        <p class="text-sm text-gray-600">Absen Hadir tersedia pukul {{ substr((string) $settings->student_check_in_starts_at, 0, 5) }}–{{ substr((string) $settings->student_check_in_ends_at, 0, 5) }} dan hanya dapat dikirim dari radius {{ $settings->radius_meters }} meter sekolah.</p>
                        <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Untuk mencatat kehadiran, lokasi perangkat diminta agar jarak ke sekolah dapat diperiksa. Lokasi disimpan bersama catatan absensi selama masa retensi administrasi sekolah sebagaimana dijelaskan di <a class="font-semibold underline" href="{{ route('legal.privacy') }}">Kebijakan Privasi</a>. Lokasi browser bukan bukti pasti dan dapat ditinjau oleh sekolah.</p>
                        <p data-location-message role="status" class="text-sm text-red-700"></p>
                        <button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white">Kirim absensi</button>
                    </form>
                @endif
            @else
                <p class="mt-4 text-sm text-gray-600">Tampilan kehadiran anak. Koreksi absensi dilakukan oleh guru kelas.</p>
            @endif
        </section>

        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm">
            <div><label for="month" class="block font-semibold">Rekap bulan</label><input id="month" name="month" type="month" value="{{ $month }}" class="mt-1 min-h-11 rounded-lg border-gray-300"></div>
            <button class="min-h-11 rounded-lg border border-gray-300 px-5 font-semibold">Tampilkan</button>
        </form>
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Jam</th><th class="px-4 py-3">Catatan</th><th class="px-4 py-3">Lampiran</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($attendance as $record)
                            <tr>
                                <td class="px-4 py-3">{{ $record->attendance_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 font-semibold">{{ ucfirst($record->status) }}@if ($record->review_status !== 'bersih')<span class="mt-1 block text-xs font-normal text-amber-800">{{ str_replace('_', ' ', ucfirst($record->review_status)) }}</span>@endif</td>
                                <td class="px-4 py-3">{{ $record->checked_in_at?->format('H:i') ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $record->correction_reason ?: $record->reason ?: '—' }}</td>
                                <td class="px-4 py-3">@if ($record->attachment_path)<a class="font-semibold text-muhammadiyah-hijau-tua underline" href="{{ route('attendance.attachment', $record) }}">Unduh berkas</a>@else — @endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-600">Belum ada absensi pada bulan ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</x-app-layout>
