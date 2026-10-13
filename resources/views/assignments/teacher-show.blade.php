<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Kiriman Tugas</h1></x-slot>

    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <p class="font-semibold text-muhammadiyah-hijau">{{ $assignment->subject->name }} · {{ $assignment->schoolClass->school_level }} {{ $assignment->schoolClass->grade }} {{ $assignment->schoolClass->name }}</p>
            <h2 class="mt-1 text-xl font-bold text-muhammadiyah-hijau-tua">{{ $assignment->title }}</h2>
            <p class="mt-2 text-sm text-gray-600">Batas waktu: {{ $assignment->due_at->format('d/m/Y H:i') }}</p>
            @if ($assignment->description)<p class="mt-3 whitespace-pre-line">{{ $assignment->description }}</p>@endif
            @if ($assignment->attachment_path)<a class="mt-3 inline-block font-semibold text-muhammadiyah-hijau-tua underline" href="{{ route('assignments.attachment', $assignment) }}">Unduh lampiran tugas</a>@endif
        </section>
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3">Murid</th><th class="px-4 py-3">Kiriman</th><th class="px-4 py-3">Status / Nilai</th><th class="px-4 py-3">Penilaian</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($students as $student)
                            @php($submission = $student->assignmentSubmissions->first())
                            <tr class="align-top">
                                <td class="px-4 py-4 font-semibold">{{ $student->user->name }}<span class="block text-xs font-normal text-gray-600">NIS {{ $student->nis }}</span></td>
                                <td class="px-4 py-4">
                                    @if ($submission)
                                        <p>{{ $submission->submitted_at->format('d/m/Y H:i') }}</p>
                                        <p class="mt-1 font-semibold {{ $submission->is_late ? 'text-red-700' : 'text-green-700' }}">{{ $submission->is_late ? 'Terlambat' : 'Tepat Waktu' }}</p>
                                        @if ($submission->body)<p class="mt-2 max-w-sm whitespace-pre-line">{{ $submission->body }}</p>@endif
                                        @if ($submission->file_path)<a class="mt-2 inline-block font-semibold text-muhammadiyah-hijau-tua underline" href="{{ route('assignments.submission-attachment', $submission) }}">Unduh kiriman</a>@endif
                                    @else
                                        <span class="text-gray-500">Belum dikumpulkan</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @if ($submission?->graded_at)
                                        <p class="font-bold">Nilai {{ $submission->score }}</p>
                                        @if ($submission->feedback)<p class="mt-1 max-w-xs whitespace-pre-line">{{ $submission->feedback }}</p>@endif
                                    @elseif ($submission)
                                        <span class="text-amber-800">Belum dinilai</span>
                                    @else
                                        <span class="text-gray-500">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @if ($submission)
                                        <form method="POST" action="{{ route('guru.assignments.grade', $submission) }}" class="min-w-56 space-y-2">
                                            @csrf @method('PUT')
                                            <label for="score-{{ $submission->id }}" class="block font-medium">Nilai (0–100)</label>
                                            <input id="score-{{ $submission->id }}" name="score" type="number" min="0" max="100" step="0.01" required value="{{ $submission->score }}" class="min-h-10 w-full rounded-lg border-gray-300">
                                            <label for="feedback-{{ $submission->id }}" class="block font-medium">Komentar</label>
                                            <textarea id="feedback-{{ $submission->id }}" name="feedback" rows="2" maxlength="5000" class="w-full rounded-lg border-gray-300">{{ $submission->feedback }}</textarea>
                                            <button class="min-h-10 rounded-lg bg-muhammadiyah-hijau px-4 font-semibold text-white">Simpan nilai</button>
                                        </form>
                                    @else
                                        <span class="text-sm text-gray-500">Tersedia setelah murid mengumpulkan.</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-600">Belum ada murid aktif di kelas ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</x-app-layout>
