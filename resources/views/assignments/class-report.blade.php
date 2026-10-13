<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Rekap Nilai Kelas</h1></x-slot>

    <main class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @php($classLabel = $schoolClass->school_level.' '.$schoolClass->grade.' '.$schoolClass->name)
        <section class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-white p-5 shadow-sm">
            <div>
                <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">{{ $classLabel }}</h2>
                <p class="text-sm text-gray-600">Tahun ajaran {{ $schoolClass->academicYear->name }}</p>
            </div>
            <a class="inline-flex min-h-11 items-center rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white" href="{{ route('guru.assignments.export', $schoolClass) }}">Ekspor rekap kelas (CSV untuk Excel)</a>
        </section>
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50">
                        <tr><th class="sticky left-0 bg-gray-50 px-4 py-3">Murid</th>
                            @foreach ($assignments as $assignment)
                                <th class="min-w-48 px-4 py-3">{{ $assignment->subject->name }}<span class="block font-semibold">{{ $assignment->title }}</span></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($students as $student)
                            <tr>
                                <td class="sticky left-0 bg-white px-4 py-3 font-semibold">{{ $student->user->name }}<span class="block text-xs font-normal text-gray-600">NIS {{ $student->nis }}</span>
                                    <a class="mt-1 inline-block text-xs font-semibold text-muhammadiyah-hijau-tua underline" href="{{ route('guru.assignments.export', ['schoolClass' => $schoolClass, 'student_profile_id' => $student->id]) }}">Ekspor nilai murid</a>
                                </td>
                                @foreach ($assignments as $assignment)
                                    @php($submission = $assignment->submissions->firstWhere('student_profile_id', $student->id))
                                    <td class="px-4 py-3">
                                        @if ($submission)
                                            <span class="font-bold">{{ $submission->score !== null ? $submission->score : 'Belum dinilai' }}</span>
                                            <span class="mt-1 block text-xs {{ $submission->is_late ? 'text-red-700' : 'text-green-700' }}">{{ $submission->is_late ? 'Terlambat' : 'Tepat Waktu' }}</span>
                                        @else
                                            <span class="text-gray-500">Belum dikumpulkan</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ max(1, $assignments->count() + 1) }}" class="px-4 py-8 text-center text-gray-600">Belum ada murid aktif di kelas ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($assignments->isEmpty())<p class="p-5 text-sm text-gray-600">Belum ada tugas pada mata pelajaran yang Anda ampu di kelas ini.</p>@endif
        </section>
    </main>
</x-app-layout>
