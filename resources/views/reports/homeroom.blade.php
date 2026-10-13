<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Rapor kelas {{ $schoolClass->name }} · {{ $schoolClass->academicYear->name }}</h1></x-slot>
    <main class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<p role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</p>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-700">Ringkasan hadir, izin, sakit, dan alpa dihitung dari absensi sekolah. Rapor terbit terkunci; perubahan perlu alasan.</p>
            <form method="POST" action="{{ route(auth()->user()->role === \App\Models\User::ROLE_ADMIN ? 'admin.reports.publish' : 'guru.reports.publish', $schoolClass) }}" class="mt-5 space-y-4">
                @csrf
                @foreach ($students as $student)
                    @php($report = $student->reportCards->first())
                    <div class="rounded-lg border p-4">
                        <label for="note-{{ $student->id }}" class="block font-semibold">{{ $student->user->name }} · {{ $report?->status ?? 'belum dibuat' }}</label>
                        <textarea id="note-{{ $student->id }}" name="notes[{{ $student->id }}]" rows="3" maxlength="2000" class="mt-2 w-full rounded-lg border-gray-300">{{ $report?->homeroom_note }}</textarea>
                    </div>
                @endforeach
                @if ($students->contains(fn ($student) => $student->reportCards->contains(fn ($report) => $report->status === 'terbit')))
                    <div><label for="reason" class="mb-1 block font-semibold">Alasan perubahan rapor terbit</label><textarea id="reason" name="reason" maxlength="2000" class="w-full rounded-lg border-gray-300"></textarea></div>
                @endif
                <label for="action" class="block font-semibold">Status</label>
                <select id="action" name="action" required class="min-h-11 w-full rounded-lg border-gray-300 sm:max-w-xs">
                    <option value="draf">Draf</option><option value="terbit">Terbitkan</option>
                </select>
                <button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-bold text-white">Simpan catatan dan rapor</button>
            </form>
        </section>
    </main>
</x-app-layout>
