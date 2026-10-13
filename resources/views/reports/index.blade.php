<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Rapor siswa</h1></x-slot>
    <main class="mx-auto max-w-6xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (isset($courses) && $courses->isNotEmpty())
            <section class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold">Kelas dan mata pelajaran</h2>
                <ul class="mt-3 divide-y">
                    @foreach ($courses as $course)
                        @php($routeName = auth()->user()->role === \App\Models\User::ROLE_ADMIN ? 'admin.reports.manage' : 'guru.reports.manage')
                        <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <span>{{ $course->schoolClass->academicYear->name }} · {{ $course->schoolClass->name }} · {{ $course->subject->name }}</span>
                            <a class="inline-flex min-h-10 items-center rounded-lg border border-muhammadiyah-hijau px-4 font-semibold text-muhammadiyah-hijau-tua" href="{{ route($routeName, [$course->schoolClass, $course->subject]) }}">Kelola nilai</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
        @if (isset($classes) && $classes->isNotEmpty())
            <section class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold">Catatan wali kelas dan penerbitan rapor</h2>
                <ul class="mt-3 divide-y">
                    @foreach ($classes as $schoolClass)
                        @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN || $schoolClass->homeroom_teacher_profile_id === auth()->user()->teacherProfile?->id)
                            @php($routeName = auth()->user()->role === \App\Models\User::ROLE_ADMIN ? 'admin.reports.homeroom' : 'guru.reports.homeroom')
                            <li class="flex items-center justify-between gap-2 py-3">
                                <span>{{ $schoolClass->academicYear->name }} · {{ $schoolClass->name }}</span>
                                <a href="{{ route($routeName, $schoolClass) }}" class="inline-flex min-h-10 items-center rounded-lg border px-4 font-semibold">Kelola rapor kelas</a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </section>
        @endif
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <h2 class="border-b p-5 text-lg font-bold">Daftar rapor</h2>
            <ul class="divide-y divide-gray-100">
                @forelse ($reports as $report)
                    <li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div>
                            @if (isset($report->student))
                                <p class="font-bold">{{ $report->student->user->name }}</p>
                            @endif
                            <p>{{ $report->academicYear->name }} · {{ ucfirst($report->status) }}</p>
                        </div>
                        <a href="{{ route('reports.pdf', $report) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-muhammadiyah-hijau px-4 font-semibold text-white">Unduh PDF</a>
                    </li>
                @empty
                    <li class="p-8 text-center text-gray-600">Belum ada rapor yang dapat ditampilkan.</li>
                @endforelse
            </ul>
            @if (method_exists($reports, 'hasPages') && $reports->hasPages())<div class="border-t p-4">{{ $reports->links() }}</div>@endif
        </section>
    </main>
</x-app-layout>
