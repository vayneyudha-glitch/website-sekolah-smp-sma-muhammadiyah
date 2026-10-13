<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Rapor {{ $student->user->name }}</h1></x-slot>
    <main class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @forelse ($reports as $report)
            <section class="rounded-xl bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-lg font-bold">{{ $report->academicYear->name }}</h2>
                    <a href="{{ route('reports.pdf', $report) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-muhammadiyah-hijau px-5 font-bold text-white">Unduh rapor PDF</a>
                </div>
                <ul class="mt-4 divide-y">
                    @foreach ($report->items as $item)
                        <li class="grid gap-1 py-3 sm:grid-cols-[2fr_1fr_1fr]"><strong>{{ $item->subject->name }}</strong><span>{{ number_format((float) $item->final_score, 2, ',', '.') }} · {{ $item->predicate }}</span><span>{{ $item->description }}</span></li>
                    @endforeach
                </ul>
                @if ($report->homeroom_note)<p class="mt-4 rounded-lg bg-gray-50 p-4"><strong>Catatan wali kelas:</strong> {{ $report->homeroom_note }}</p>@endif
            </section>
        @empty
            <p class="rounded-xl bg-white p-8 text-center text-gray-600">Belum ada rapor yang diterbitkan.</p>
        @endforelse
    </main>
</x-app-layout>
