<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Nilai {{ $subject->name }} · {{ $schoolClass->name }} · {{ $schoolClass->academicYear->name }}</h1></x-slot>
    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<p role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</p>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @php($configured = $components->isNotEmpty())
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold">Komponen dan bobot (total 100%)</h2>
            <form method="POST" action="{{ route(auth()->user()->role === \App\Models\User::ROLE_ADMIN ? 'admin.reports.components' : 'guru.reports.components', [$schoolClass, $subject]) }}" class="mt-4 space-y-3">
                @csrf
                @if ($configured)
                    @foreach ($components as $index => $component)
                        <div class="grid gap-2 sm:grid-cols-[2fr_1fr]">
                            <label class="sr-only" for="component-name-{{ $index }}">Nama komponen {{ $index + 1 }}</label>
                            <input id="component-name-{{ $index }}" name="components[{{ $index }}][name]" value="{{ $component->name }}" required maxlength="100" class="min-h-11 rounded-lg border-gray-300">
                            <label class="flex items-center gap-2"><input name="components[{{ $index }}][weight]" type="number" min="0.01" max="100" step="0.01" value="{{ $component->weight }}" required class="min-h-11 w-32 rounded-lg border-gray-300"> %</label>
                        </div>
                    @endforeach
                @else
                    @foreach ([['Tugas', 40], ['Ulangan Harian', 30], ['UTS', 15], ['UAS', 15]] as $index => $default)
                        <div class="grid gap-2 sm:grid-cols-[2fr_1fr]">
                            <label class="sr-only" for="component-name-{{ $index }}">Nama komponen {{ $index + 1 }}</label>
                            <input id="component-name-{{ $index }}" name="components[{{ $index }}][name]" value="{{ $default[0] }}" required maxlength="100" class="min-h-11 rounded-lg border-gray-300">
                            <label class="flex items-center gap-2"><input name="components[{{ $index }}][weight]" type="number" min="0.01" max="100" step="0.01" value="{{ $default[1] }}" required class="min-h-11 w-32 rounded-lg border-gray-300"> %</label>
                        </div>
                    @endforeach
                @endif
                <p class="text-sm text-gray-600">Komponen bernama “Tugas” juga menghitung nilai tugas yang telah dinilai.</p>
                @if ($hasPublishedSubject)
                    <div><label for="component-reason" class="mb-1 block font-semibold">Alasan perubahan bobot setelah rapor terbit</label><textarea id="component-reason" name="reason" required maxlength="2000" class="w-full rounded-lg border-gray-300"></textarea></div>
                @endif
                <button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-bold text-white">Simpan komponen</button>
            </form>
        </section>

        @if ($components->isNotEmpty())
            <section class="overflow-hidden rounded-xl bg-white shadow-sm">
                <h2 class="p-5 text-lg font-bold">Nilai tambahan per komponen</h2>
                <form method="POST" action="{{ route(auth()->user()->role === \App\Models\User::ROLE_ADMIN ? 'admin.reports.scores' : 'guru.reports.scores', [$schoolClass, $subject]) }}">
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-left text-sm">
                            <thead class="bg-gray-50"><tr><th class="p-3">Murid</th>@foreach ($components as $component)<th class="p-3">{{ $component->name }} ({{ $component->weight }}%)</th>@endforeach</tr></thead>
                            <tbody class="divide-y">
                                @foreach ($students as $student)
                                    <tr><th class="p-3 font-medium">{{ $student->user->name }}</th>
                                        @foreach ($components as $component)
                                            @php($grade = $component->scores->firstWhere('student_profile_id', $student->id)?->score)
                                            <td class="p-3"><label class="sr-only" for="score-{{ $component->id }}-{{ $student->id }}">{{ $component->name }} — {{ $student->user->name }}</label><input id="score-{{ $component->id }}-{{ $student->id }}" name="scores[{{ $component->id }}][{{ $student->id }}]" type="number" min="0" max="100" step="0.01" value="{{ $grade }}" class="min-h-10 w-24 rounded-lg border-gray-300"></td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($hasPublishedYearReports)
                        <div class="p-4"><label for="score-reason" class="mb-1 block font-semibold">Alasan perubahan nilai setelah rapor terbit</label><textarea id="score-reason" name="reason" required maxlength="2000" class="w-full rounded-lg border-gray-300"></textarea></div>
                    @endif
                    <div class="p-4"><button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-bold text-white">Simpan nilai</button></div>
                </form>
            </section>
        @endif

        @if ($isHomeroom)
            <section class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold">Catatan dan rapor kelas</h2>
                <p class="mt-1 text-sm text-gray-600">Nilai rapor disalin sebagai cuplikan saat disimpan. Setelah terbit, perubahan memerlukan alasan dan tercatat di audit.</p>
                <form method="POST" action="{{ route(auth()->user()->role === \App\Models\User::ROLE_ADMIN ? 'admin.reports.publish' : 'guru.reports.publish', $schoolClass) }}" class="mt-4 space-y-4">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($students as $student)
                            @php($report = $reports->get($student->id))
                            <div class="rounded-lg border p-4">
                                <label for="note-{{ $student->id }}" class="block font-semibold">{{ $student->user->name }} @if ($report)<span class="text-sm text-gray-600">({{ $report->status }})</span>@endif</label>
                                <textarea id="note-{{ $student->id }}" name="notes[{{ $student->id }}]" rows="2" maxlength="2000" class="mt-2 w-full rounded-lg border-gray-300">{{ old('notes.'.$student->id, $report?->homeroom_note) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                    @if ($reports->contains(fn ($report) => $report->status === 'terbit'))
                        <div><label for="report-reason" class="mb-1 block font-semibold">Alasan perubahan rapor terbit</label><textarea id="report-reason" name="reason" maxlength="2000" class="w-full rounded-lg border-gray-300"></textarea></div>
                    @endif
                    <label for="report-action" class="block font-semibold">Simpan sebagai</label>
                    <select id="report-action" name="action" required class="min-h-11 w-full rounded-lg border-gray-300 sm:max-w-xs">
                        <option value="draf">Draf</option><option value="terbit">Terbitkan</option>
                    </select>
                    <button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-bold text-white">Simpan rapor kelas</button>
                </form>
            </section>
        @endif

        @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
            <section class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold">Predikat dan deskripsi · {{ $schoolClass->academicYear->name }}</h2>
                <form method="POST" action="{{ route('admin.reports.bands', $schoolClass) }}" class="mt-4 space-y-3">
                    @csrf @method('PUT')
                    @forelse ($bands as $index => $band)
                        <div class="grid gap-2 sm:grid-cols-[1fr_1fr_2fr]">
                            <label class="sr-only" for="band-min-{{ $index }}">Nilai minimum</label><input id="band-min-{{ $index }}" name="bands[{{ $index }}][minimum_score]" type="number" min="0" max="100" step="0.01" value="{{ $band->minimum_score }}" required class="min-h-11 rounded-lg border-gray-300">
                            <label class="sr-only" for="band-predicate-{{ $index }}">Predikat</label><input id="band-predicate-{{ $index }}" name="bands[{{ $index }}][predicate]" value="{{ $band->predicate }}" required maxlength="20" class="min-h-11 rounded-lg border-gray-300">
                            <label class="sr-only" for="band-description-{{ $index }}">Deskripsi</label><input id="band-description-{{ $index }}" name="bands[{{ $index }}][description]" value="{{ $band->description }}" required maxlength="500" class="min-h-11 rounded-lg border-gray-300">
                        </div>
                    @empty
                        @foreach ([[0, 'D', 'Perlu bimbingan'], [70, 'C', 'Cukup'], [80, 'B', 'Baik'], [90, 'A', 'Sangat baik']] as $index => $band)
                            <div class="grid gap-2 sm:grid-cols-[1fr_1fr_2fr]">
                                <label class="sr-only" for="band-min-{{ $index }}">Nilai minimum</label><input id="band-min-{{ $index }}" name="bands[{{ $index }}][minimum_score]" type="number" min="0" max="100" step="0.01" value="{{ $band[0] }}" required class="min-h-11 rounded-lg border-gray-300">
                                <label class="sr-only" for="band-predicate-{{ $index }}">Predikat</label><input id="band-predicate-{{ $index }}" name="bands[{{ $index }}][predicate]" value="{{ $band[1] }}" required maxlength="20" class="min-h-11 rounded-lg border-gray-300">
                                <label class="sr-only" for="band-description-{{ $index }}">Deskripsi</label><input id="band-description-{{ $index }}" name="bands[{{ $index }}][description]" value="{{ $band[2] }}" required maxlength="500" class="min-h-11 rounded-lg border-gray-300">
                            </div>
                        @endforeach
                    @endforelse
                    @if ($reports->contains(fn ($report) => $report->status === 'terbit'))
                        <div><label for="bands-reason" class="mb-1 block font-semibold">Alasan perubahan predikat setelah rapor terbit</label><textarea id="bands-reason" name="reason" required maxlength="2000" class="w-full rounded-lg border-gray-300"></textarea></div>
                    @endif
                    <button class="min-h-11 rounded-lg border border-muhammadiyah-hijau px-5 font-bold text-muhammadiyah-hijau-tua">Simpan predikat</button>
                </form>
            </section>
        @endif
    </main>
</x-app-layout>
