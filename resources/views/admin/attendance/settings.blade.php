<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Pengaturan Absensi Sekolah</h1></x-slot>

    <main class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
            <p class="mb-4 text-gray-700">Klik peta untuk menetapkan koordinat sekolah. Peta menggunakan Leaflet dan OpenStreetMap tanpa API berbayar.</p>
            <div id="school-map" class="h-80 overflow-hidden rounded-lg border border-gray-300" aria-label="Peta untuk memilih lokasi sekolah"></div>
            <form method="POST" action="{{ route('admin.attendance.settings.update') }}" class="mt-6 space-y-5">
                @csrf @method('PUT')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="latitude" class="block font-semibold">Latitude</label><input id="latitude" name="latitude" type="number" step="any" required value="{{ old('latitude', $settings->latitude) }}" class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                    <div><label for="longitude" class="block font-semibold">Longitude</label><input id="longitude" name="longitude" type="number" step="any" required value="{{ old('longitude', $settings->longitude) }}" class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                </div>
                <div>
                    <label for="radius_meters" class="block font-semibold">Radius absensi (meter)</label>
                    <input id="radius_meters" name="radius_meters" type="number" min="20" max="5000" required value="{{ old('radius_meters', $settings->radius_meters ?: 100) }}" class="mt-1 min-h-11 w-full rounded-lg border-gray-300">
                    <p class="mt-1 text-sm text-gray-600">Nilai awal 100 meter.</p>
                </div>
                <fieldset class="grid gap-4 rounded-lg border border-gray-200 p-4 sm:grid-cols-2">
                    <legend class="px-2 font-bold">Jam absen Hadir murid</legend>
                    <div><label for="student_check_in_starts_at" class="block font-semibold">Mulai</label><input id="student_check_in_starts_at" name="student_check_in_starts_at" type="time" required value="{{ old('student_check_in_starts_at', substr((string) $settings->student_check_in_starts_at, 0, 5)) }}" class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                    <div><label for="student_check_in_ends_at" class="block font-semibold">Selesai</label><input id="student_check_in_ends_at" name="student_check_in_ends_at" type="time" required value="{{ old('student_check_in_ends_at', substr((string) $settings->student_check_in_ends_at, 0, 5)) }}" class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                </fieldset>
                <fieldset class="grid gap-4 rounded-lg border border-gray-200 p-4 sm:grid-cols-2">
                    <legend class="px-2 font-bold">Jam absen masuk guru</legend>
                    <div><label for="teacher_check_in_starts_at" class="block font-semibold">Mulai</label><input id="teacher_check_in_starts_at" name="teacher_check_in_starts_at" type="time" required value="{{ old('teacher_check_in_starts_at', substr((string) $settings->teacher_check_in_starts_at, 0, 5)) }}" class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                    <div><label for="teacher_check_in_ends_at" class="block font-semibold">Selesai</label><input id="teacher_check_in_ends_at" name="teacher_check_in_ends_at" type="time" required value="{{ old('teacher_check_in_ends_at', substr((string) $settings->teacher_check_in_ends_at, 0, 5)) }}" class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                </fieldset>
                <div class="flex flex-wrap gap-3">
                    <button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white">Simpan pengaturan</button>
                    <a href="{{ route('admin.attendance.teachers') }}" class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 px-5 font-semibold">Rekap absensi guru</a>
                </div>
            </form>
        </section>
    </main>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const schoolLatInput = document.getElementById('latitude');
        const schoolLngInput = document.getElementById('longitude');
        const hasSchoolCoordinates = schoolLatInput.value !== '' && schoolLngInput.value !== '';
        const schoolMap = L.map('school-map').setView(
            hasSchoolCoordinates ? [Number(schoolLatInput.value), Number(schoolLngInput.value)] : [-6.5712, 107.7587],
            hasSchoolCoordinates ? 17 : 13
        );
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(schoolMap);
        let schoolMarker = hasSchoolCoordinates
            ? L.marker([Number(schoolLatInput.value), Number(schoolLngInput.value)]).addTo(schoolMap)
            : null;
        schoolMap.on('click', (event) => {
            schoolLatInput.value = event.latlng.lat.toFixed(7);
            schoolLngInput.value = event.latlng.lng.toFixed(7);
            if (schoolMarker) schoolMarker.setLatLng(event.latlng);
            else schoolMarker = L.marker(event.latlng).addTo(schoolMap);
        });
    </script>
</x-app-layout>
