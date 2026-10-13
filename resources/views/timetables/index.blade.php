<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-white">Jadwal Pelajaran</h1>
    </x-slot>

    <main class="mx-auto max-w-5xl space-y-4 px-4 py-6 sm:px-6 lg:px-8">
        <p class="text-gray-700">Jadwal tahun ajaran yang sedang aktif, sesuai kelas atau tugas Anda.</p>
        @forelse ($timetables->groupBy('day_of_week') as $day => $dayTimetables)
            <section class="overflow-hidden rounded-xl bg-white shadow-sm">
                <h2 class="bg-muhammadiyah-hijau px-4 py-3 font-bold text-white">{{ [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'][$day] }}</h2>
                <ul class="divide-y divide-gray-100">
                    @foreach ($dayTimetables as $timetable)
                        <li class="p-4 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <h3 class="font-bold text-muhammadiyah-hijau-tua">{{ $timetable->subject->name }}</h3>
                                <p class="mt-1 text-sm text-gray-700">{{ $timetable->schoolClass->name }} · {{ $timetable->teacher->user->name }}</p>
                                @if ($timetable->location)<p class="mt-1 text-sm text-gray-600">Ruang {{ $timetable->location }}</p>@endif
                            </div>
                            <p class="mt-3 font-semibold text-gray-900 sm:mt-0">{{ substr($timetable->starts_at, 0, 5) }}–{{ substr($timetable->ends_at, 0, 5) }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <section class="rounded-xl bg-white p-8 text-center text-gray-700 shadow-sm">Belum ada jadwal untuk Anda pada tahun ajaran aktif.</section>
        @endforelse
    </main>
</x-app-layout>
