<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Pratinjau kenaikan kelas</h1></x-slot>
    <main class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <p>Tahun ajaran <strong>{{ $source->name }}</strong> → <strong>{{ $target->name }}</strong></p>
            <ul class="mt-3 divide-y">
                @foreach ($preview as $row)
                    <li class="grid gap-1 py-3 sm:grid-cols-3"><span>{{ $row['name'] }}</span><span>{{ $row['from'] }}</span><strong>{{ $row['to'] }}</strong></li>
                @endforeach
            </ul>
        </section>
        <form method="POST" action="{{ route('admin.school-years.advance.execute') }}" class="flex flex-col gap-3 sm:flex-row">
            @csrf
            <input type="hidden" name="academic_year_id" value="{{ $target->id }}">
            @foreach ($mappings as $classId => $destination)<input type="hidden" name="mapping[{{ $classId }}]" value="{{ $destination }}">@endforeach
            @foreach ($repeatTo as $studentId => $classId)<input type="hidden" name="repeat_to[{{ $studentId }}]" value="{{ $classId }}">@endforeach
            <a href="{{ route('admin.school-years.advance.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-lg border px-5 font-semibold">Kembali</a>
            <button onclick="return confirm('Proses kenaikan kelas ini? Data akan disimpan dalam satu transaksi.')" class="min-h-12 rounded-lg bg-muhammadiyah-hijau px-6 font-bold text-white">Konfirmasi dan proses</button>
        </form>
    </main>
</x-app-layout>
