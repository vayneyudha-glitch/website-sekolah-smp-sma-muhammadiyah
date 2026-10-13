<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Absensi Guru</h1></x-slot>

    <main class="mx-auto max-w-3xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <section class="space-y-4 rounded-xl bg-white p-5 shadow-sm sm:p-7">
            <p class="text-gray-700">Absen masuk: pukul {{ substr((string) $settings->teacher_check_in_starts_at, 0, 5) }}–{{ substr((string) $settings->teacher_check_in_ends_at, 0, 5) }}. Absensi masuk dan pulang memerlukan lokasi dalam radius {{ $settings->radius_meters }} meter serta selfie.</p>
            @if (! $settings->hasSchoolLocation())
                <p class="rounded-lg bg-amber-50 p-3 text-amber-900">Koordinat sekolah belum diatur. Silakan hubungi administrator.</p>
            @elseif (! $attendance || ! $attendance->check_in_at)
                <form method="POST" action="{{ route('guru.attendance.check-in') }}" enctype="multipart/form-data" data-location-form data-camera-form data-challenge-type="teacher_check_in" class="space-y-4">
                    @csrf
                    <input type="hidden" name="attendance_challenge">
                    @include('attendance.partials.location-selfie')
                    <button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white">Absen masuk</button>
                </form>
            @else
                <div class="rounded-lg bg-green-50 p-4 text-green-900">
                    <p class="font-semibold">Absen masuk tercatat pukul {{ $attendance->check_in_at->format('H:i') }}.</p>
                    @if ($attendance->check_in_selfie_path)<a class="text-sm font-semibold underline" href="{{ route('attendance.teacher-selfie', [$attendance, 'masuk']) }}">Lihat selfie masuk</a>@endif
                    @if (! $attendance->check_out_at)
                        <form method="POST" action="{{ route('guru.attendance.check-out') }}" enctype="multipart/form-data" data-location-form data-camera-form data-challenge-type="teacher_check_out" class="mt-4 space-y-4">
                            @csrf
                            <input type="hidden" name="attendance_challenge">
                            @include('attendance.partials.location-selfie')
                            <button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white">Absen pulang</button>
                        </form>
                    @else
                        <p class="mt-1">Absen pulang tercatat pukul {{ $attendance->check_out_at->format('H:i') }}.</p>
                        @if ($attendance->check_out_selfie_path)<a class="mt-1 block text-sm font-semibold underline" href="{{ route('attendance.teacher-selfie', [$attendance, 'pulang']) }}">Lihat selfie pulang</a>@endif
                    @endif
                </div>
            @endif
            @if ($attendance?->is_suspicious)
                <div class="rounded-lg bg-amber-50 p-4 text-amber-900">
                    @if ($attendance->review_status === 'perlu_ditinjau')
                        <p class="font-semibold">Absensi menunggu tinjauan admin.</p>
                    @elseif ($attendance->review_status === 'disetujui')
                        <p class="font-semibold">Tinjauan admin: absensi disetujui.</p>
                    @elseif ($attendance->review_status === 'ditolak')
                        <p class="font-semibold">Tinjauan admin: absensi ditolak.</p>
                    @endif
                    <ul class="mt-1 list-inside list-disc text-sm">@foreach ($attendance->suspicious_reasons ?? [] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
                    @if ($attendance->review_note)<p class="mt-2 text-sm">Catatan admin: {{ $attendance->review_note }}</p>@endif
                </div>
            @endif
        </section>
        <p class="text-sm text-gray-600">Deteksi lokasi dan penggunaan perangkat adalah perkiraan, bukan bukti pasti GPS palsu. Jika izin lokasi ditolak, izinkan akses lokasi di browser lalu muat ulang halaman. GPS browser hanya berjalan melalui HTTPS atau localhost.</p>
        <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Lokasi diminta untuk memeriksa jarak absensi dan disimpan bersama catatan absensi selama masa retensi administrasi sekolah sebagaimana dijelaskan di <a class="font-semibold underline" href="{{ route('legal.privacy') }}">Kebijakan Privasi</a>. Selfie hanya dipakai sebagai bukti absensi guru dan disimpan privat.</p>
    </main>
</x-app-layout>
