<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Pratinjau impor</h1></x-slot>
    <main class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold">{{ ['students' => 'Murid', 'teachers' => 'Guru', 'parents' => 'Orang tua', 'classes' => 'Kelas'][$batch->type] }}</h2>
            <p class="mt-2">Baris valid: <strong>{{ $validCount }}</strong> · Baris bermasalah: <strong>{{ $invalidCount }}</strong></p>
            <p class="mt-1 text-sm text-gray-600">Pratinjau hanya menampilkan nomor baris dan alasan validasi; data pribadi tidak ditampilkan.</p>
        </section>
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <ul class="divide-y divide-gray-100">
                @foreach ($rows as $row)
                    <li class="p-4">
                        <p class="font-semibold">Baris {{ $row['number'] }} — {{ $row['valid'] ? 'Valid' : 'Perlu diperbaiki' }}</p>
                        @if (! $row['valid'])
                            <ul class="mt-1 list-inside list-disc text-sm text-red-800">
                                @foreach ($row['errors'] as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
        <div class="flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('admin.imports.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-lg border px-5 font-semibold">Batal</a>
            @if ($invalidCount === 0)
                <form method="POST" action="{{ route('admin.imports.confirm', $batch) }}">
                    @csrf
                    <button class="min-h-12 rounded-lg bg-muhammadiyah-hijau px-6 font-bold text-white">Konfirmasi dan simpan {{ $validCount }} baris</button>
                </form>
            @else
                <p class="self-center text-sm font-semibold text-red-800">Seluruh baris harus valid sebelum impor dapat disimpan.</p>
            @endif
        </div>
    </main>
</x-app-layout>
