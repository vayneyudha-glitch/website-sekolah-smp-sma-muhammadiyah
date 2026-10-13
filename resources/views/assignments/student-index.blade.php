<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Tugas Saya</h1></x-slot>

    <main class="mx-auto max-w-5xl space-y-4 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <p class="text-sm text-gray-700">Kiriman dinilai berdasarkan waktu server. Berkas privat: PDF, DOCX, JPG, atau PNG, maks. 5 MB. Anda dapat mengganti kiriman sebelum dinilai.</p>
        @forelse ($assignments as $assignment)
            @php($submission = $assignment->submissions->first())
            <section class="rounded-xl bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-muhammadiyah-hijau">{{ $assignment->subject->name }}</p>
                        <h2 class="mt-1 text-lg font-bold text-muhammadiyah-hijau-tua">{{ $assignment->title }}</h2>
                    </div>
                    <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $submission ? ($submission->is_late ? 'bg-red-50 text-red-800' : 'bg-green-50 text-green-800') : 'bg-amber-50 text-amber-900' }}">
                        {{ $submission ? ($submission->is_late ? 'Terlambat' : 'Tepat Waktu') : 'Belum dikumpulkan' }}
                    </span>
                </div>
                <p class="mt-2 text-sm text-gray-600">Guru: {{ $assignment->teacher->name }} · Batas waktu: {{ $assignment->due_at->format('d/m/Y H:i') }}</p>
                @if ($assignment->description)<p class="mt-3 whitespace-pre-line text-gray-800">{{ $assignment->description }}</p>@endif
                @if ($assignment->attachment_path)<a class="mt-3 inline-block font-semibold text-muhammadiyah-hijau-tua underline" href="{{ route('assignments.attachment', $assignment) }}">Unduh lampiran tugas</a>@endif

                @if ($submission?->graded_at)
                    <div class="mt-4 rounded-lg bg-blue-50 p-4 text-blue-950">
                        <p class="font-bold">Nilai: {{ $submission->score }}</p>
                        @if ($submission->feedback)<p class="mt-1 whitespace-pre-line">Komentar guru: {{ $submission->feedback }}</p>@endif
                        <p class="mt-1 text-sm">Dinilai {{ $submission->graded_at->format('d/m/Y H:i') }}</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('siswa.assignments.submit', $assignment) }}" enctype="multipart/form-data" class="mt-4 space-y-3 border-t border-gray-100 pt-4">
                        @csrf
                        <div>
                            <label for="body-{{ $assignment->id }}" class="block font-semibold">Jawaban teks (opsional jika mengunggah berkas)</label>
                            <textarea id="body-{{ $assignment->id }}" name="body" rows="3" maxlength="10000" class="mt-1 w-full rounded-lg border-gray-300">{{ $submission?->body }}</textarea>
                        </div>
                        <div>
                            <label for="submission-file-{{ $assignment->id }}" class="block font-semibold">Unggah berkas (opsional jika mengisi teks)</label>
                            <input id="submission-file-{{ $assignment->id }}" name="attachment" type="file" accept=".pdf,.docx,.jpg,.jpeg,.png" class="mt-1 block min-h-10 w-full text-sm">
                            @if ($submission?->file_path)<a class="mt-1 inline-block text-sm font-semibold text-muhammadiyah-hijau-tua underline" href="{{ route('assignments.submission-attachment', $submission) }}">Unduh kiriman saat ini</a>@endif
                        </div>
                        <button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white">{{ $submission ? 'Ganti / simpan kiriman' : 'Kirim tugas' }}</button>
                    </form>
                @endif
            </section>
        @empty
            <section class="rounded-xl bg-white p-6 text-center text-gray-700 shadow-sm">Belum ada tugas untuk kelas Anda.</section>
        @endforelse
    </main>
</x-app-layout>
