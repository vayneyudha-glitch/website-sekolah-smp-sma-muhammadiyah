<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Nilai Tugas Anak</h1></x-slot>

    <main class="mx-auto max-w-5xl space-y-4 px-4 py-6 sm:px-6 lg:px-8">
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">{{ $student->name }}</h2>
            <p class="mt-1 text-gray-600">Kelas {{ $profile->schoolClass->school_level }} {{ $profile->schoolClass->grade }} {{ $profile->schoolClass->name }}</p>
        </section>
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3">Mata pelajaran / tugas</th><th class="px-4 py-3">Nilai</th><th class="px-4 py-3">Status pengumpulan</th><th class="px-4 py-3">Komentar guru</th><th class="px-4 py-3">Dinilai</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($submissions as $submission)
                            <tr>
                                <td class="px-4 py-4"><span class="block font-semibold">{{ $submission->assignment->subject->name }}</span>{{ $submission->assignment->title }}</td>
                                <td class="px-4 py-4 font-bold">{{ $submission->score }}</td>
                                <td class="px-4 py-4 {{ $submission->is_late ? 'text-red-700' : 'text-green-700' }}">{{ $submission->is_late ? 'Terlambat' : 'Tepat Waktu' }}</td>
                                <td class="px-4 py-4 whitespace-pre-line">{{ $submission->feedback ?: '—' }}</td>
                                <td class="px-4 py-4">{{ $submission->graded_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-600">Belum ada nilai tugas untuk ditampilkan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</x-app-layout>
