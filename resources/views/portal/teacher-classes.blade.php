<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-white">Kelas yang Diampu</h1>
    </x-slot>

    <main class="mx-auto max-w-5xl space-y-4 px-4 py-6 sm:px-6 lg:px-8">
        @forelse ($classes as $class)
            <section class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="bg-muhammadiyah-hijau px-5 py-4 text-white">
                    <h2 class="text-lg font-bold">{{ $class->school_level }} {{ $class->grade }} {{ $class->name }}</h2>
                    <p class="text-sm text-white/90">Tahun ajaran {{ $class->academicYear->name }}</p>
                    <a href="{{ route('guru.classes.attendance', $class) }}" class="mt-2 inline-flex min-h-10 items-center rounded-lg bg-white px-4 font-semibold text-muhammadiyah-hijau-tua">Rekap dan koreksi absensi</a>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($class->students as $student)
                        <li class="flex flex-col gap-1 p-4 sm:flex-row sm:justify-between">
                            <span class="font-semibold text-gray-900">{{ $student->user->name }}</span>
                            <span class="text-sm text-gray-600">NIS: {{ $student->nis }} · NISN: {{ $student->nisn ?: '—' }} · {{ ucfirst($student->status) }}</span>
                        </li>
                    @empty
                        <li class="p-4 text-gray-600">Belum ada murid terdaftar di kelas ini.</li>
                    @endforelse
                </ul>
            </section>
        @empty
            <section class="rounded-xl bg-white p-6 text-center text-gray-700 shadow-sm">Belum ada kelas yang ditugaskan ke akun Anda.</section>
        @endforelse
    </main>
</x-app-layout>
