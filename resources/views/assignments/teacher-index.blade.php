<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Tugas dan Penilaian</h1></x-slot>

    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Buat tugas baru</h2>
            <p class="mt-1 text-sm text-gray-600">Pilih kelas dan mata pelajaran yang tercantum pada jadwal mengajar Anda. Lampiran privat: PDF, DOCX, JPG, atau PNG, maks. 5 MB.</p>
            <form method="POST" action="{{ route('guru.assignments.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 md:grid-cols-2">
                @csrf
                <div class="md:col-span-2">
                    <label for="timetable_id" class="block font-semibold">Kelas dan mata pelajaran</label>
                    <select id="timetable_id" name="timetable_id" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300">
                        <option value="">Pilih kelas dan mata pelajaran</option>
                        @foreach ($timetables as $timetable)
                            <option value="{{ $timetable->id }}" @selected(old('timetable_id') == $timetable->id)>{{ $timetable->schoolClass->school_level }} {{ $timetable->schoolClass->grade }} {{ $timetable->schoolClass->name }} — {{ $timetable->subject->name }} ({{ $timetable->schoolClass->academicYear->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="title" class="block font-semibold">Judul tugas</label>
                    <input id="title" name="title" type="text" maxlength="255" required value="{{ old('title') }}" class="mt-1 min-h-11 w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label for="due_at" class="block font-semibold">Batas waktu</label>
                    <input id="due_at" name="due_at" type="datetime-local" required value="{{ old('due_at') }}" class="mt-1 min-h-11 w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label for="attachment" class="block font-semibold">Lampiran (opsional)</label>
                    <input id="attachment" name="attachment" type="file" accept=".pdf,.docx,.jpg,.jpeg,.png" class="mt-1 block min-h-11 w-full text-sm">
                </div>
                <div class="md:col-span-2">
                    <label for="description" class="block font-semibold">Deskripsi</label>
                    <textarea id="description" name="description" rows="4" maxlength="10000" class="mt-1 w-full rounded-lg border-gray-300">{{ old('description') }}</textarea>
                </div>
                <div><button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white">Buat tugas</button></div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="border-b border-gray-100 p-5"><h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Tugas pada kelas dan mata pelajaran Anda</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3">Tugas</th><th class="px-4 py-3">Kelas / Mapel</th><th class="px-4 py-3">Batas waktu</th><th class="px-4 py-3">Belum dinilai</th><th class="px-4 py-3">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($assignments as $assignment)
                            <tr>
                                <td class="px-4 py-4 font-semibold">{{ $assignment->title }}</td>
                                <td class="px-4 py-4">{{ $assignment->schoolClass->school_level }} {{ $assignment->schoolClass->grade }} {{ $assignment->schoolClass->name }}<span class="block text-gray-600">{{ $assignment->subject->name }}</span></td>
                                <td class="px-4 py-4">{{ $assignment->due_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-4">{{ $assignment->ungraded_submissions_count }}</td>
                                <td class="px-4 py-4"><a class="font-semibold text-muhammadiyah-hijau-tua underline" href="{{ route('guru.assignments.show', $assignment) }}">Lihat kiriman dan nilai</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-600">Belum ada tugas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Rekap nilai per kelas</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($classes as $schoolClass)
                    <a class="inline-flex min-h-10 items-center rounded-lg border border-muhammadiyah-hijau px-4 font-semibold text-muhammadiyah-hijau-tua" href="{{ route('guru.assignments.class-report', $schoolClass) }}">Rekap {{ $schoolClass->school_level }} {{ $schoolClass->grade }} {{ $schoolClass->name }}</a>
                @endforeach
                @if ($classes->isEmpty())<p class="text-sm text-gray-600">Belum ada kelas yang terhubung dengan akun Anda.</p>@endif
            </div>
        </section>
    </main>
</x-app-layout>
