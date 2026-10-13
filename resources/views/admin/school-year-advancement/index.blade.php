<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Wizard tahun ajaran baru dan kenaikan kelas</h1></x-slot>
    <main class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<p role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</p>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <form method="POST" action="{{ route('admin.school-years.advance.preview') }}" class="space-y-5">
            @csrf
            <section class="rounded-xl bg-white p-5 shadow-sm">
                <p>Tahun ajaran aktif: <strong>{{ $source->name }}</strong></p>
                <label for="academic_year_id" class="mt-4 block font-semibold">Tahun ajaran baru</label>
                <select id="academic_year_id" name="academic_year_id" required class="min-h-12 w-full rounded-lg border-gray-300 sm:max-w-lg">
                    <option value="">Pilih tahun ajaran</option>
                    @foreach ($years as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach
                </select>
            </section>
            <section class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="font-bold">Petakan kelas lama ke kelas baru</h2>
                @foreach ($classes as $class)
                    <div class="mt-4 grid gap-2 sm:grid-cols-[1fr_2fr] sm:items-center">
                        <label for="mapping-{{ $class->id }}">{{ $class->name }} ({{ $class->students_count }} murid)</label>
                        <select id="mapping-{{ $class->id }}" name="mapping[{{ $class->id }}]" required class="min-h-12 rounded-lg border-gray-300">
                            <option value="">Pilih kelas tujuan</option><option value="lulus">Lulus / alumni</option>
                            @foreach ($targetClasses as $targetClass)
                                <option value="{{ $targetClass->id }}">{{ $targetClass->academicYear->name }} — {{ $targetClass->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </section>
            <section class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="font-bold">Pengulangan kelas per murid</h2>
                <p class="mt-1 text-sm text-gray-600">Pilih kelas tujuan khusus untuk murid yang tidak naik. Jika mengikuti pemetaan, biarkan pilihan default.</p>
                <div class="mt-3 space-y-3">
                    @foreach ($students as $student)
                        <div class="grid gap-2 rounded-lg border border-gray-200 p-3 sm:grid-cols-[1fr_2fr] sm:items-center">
                            <label for="repeat-{{ $student->id }}">{{ $student->user->name }} — {{ $student->schoolClass->name }}</label>
                            <select id="repeat-{{ $student->id }}" name="repeat_to[{{ $student->id }}]" class="min-h-11 rounded-lg border-gray-300">
                                <option value="">Ikuti pemetaan kelas di atas</option>
                                @foreach ($targetClasses as $targetClass)
                                    <option value="{{ $targetClass->id }}">{{ $targetClass->academicYear->name }} — {{ $targetClass->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </section>
            <button class="min-h-12 rounded-lg bg-muhammadiyah-hijau px-6 font-bold text-white">Pratinjau daftar murid</button>
        </form>
    </main>
</x-app-layout>
