<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-white">Ekspor laporan</h1></x-slot>
    <main class="mx-auto max-w-4xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">
        <p class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">PDF dibatasi maksimal 1.000 baris. Gunakan rentang tanggal atau kelas untuk hasil yang lebih kecil; Excel diproses bertahap.</p>
        @foreach ($datasets as $key => $label)
            <form method="GET" action="{{ route($routeName, ['dataset' => $key, 'format' => 'xlsx']) }}" class="grid gap-3 rounded-xl bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
                <div class="sm:col-span-2 lg:col-span-1">
                    <p class="font-bold text-muhammadiyah-hijau-tua">{{ $label }}</p>
                    <label class="mt-2 block text-sm font-semibold" for="format-{{ $key }}">Format</label>
                    <select id="format-{{ $key }}" class="mt-1 min-h-10 w-full rounded-lg border-gray-300" onchange="this.form.action=this.form.action.replace(/\/(xlsx|pdf)(\?.*)?$/, '/'+this.value)">
                        <option value="xlsx">Excel</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>
                <div><label for="from-{{ $key }}" class="block text-sm font-semibold">Dari tanggal</label><input id="from-{{ $key }}" type="date" name="from" class="mt-1 min-h-10 w-full rounded-lg border-gray-300"></div>
                <div><label for="to-{{ $key }}" class="block text-sm font-semibold">Sampai tanggal</label><input id="to-{{ $key }}" type="date" name="to" class="mt-1 min-h-10 w-full rounded-lg border-gray-300"></div>
                @if (in_array($key, ['students', 'student-attendance', 'attendance', 'grades', 'payments', 'ppdb'], true))
                    <div>
                        <label for="class-{{ $key }}" class="block text-sm font-semibold">Kelas</label>
                        <select id="class-{{ $key }}" name="school_class_id" class="mt-1 min-h-10 w-full rounded-lg border-gray-300">
                            <option value="">Semua kelas</option>
                            @foreach ($classes as $schoolClass)
                                <option value="{{ $schoolClass->id }}">{{ $schoolClass->school_level }} {{ $schoolClass->grade }} {{ $schoolClass->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if (in_array($key, ['student-attendance', 'teacher-attendance', 'attendance', 'payments', 'ppdb'], true))
                    <div>
                        <label for="status-{{ $key }}" class="block text-sm font-semibold">Status</label>
                        <select id="status-{{ $key }}" name="status" class="mt-1 min-h-10 w-full rounded-lg border-gray-300">
                            <option value="">Semua status</option>
                            @foreach (match ($key) {
                                'student-attendance', 'attendance' => ['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'],
                                'teacher-attendance' => ['mencurigakan' => 'Perlu ditinjau', 'normal' => 'Normal'],
                                'payments' => ['menunggu' => 'Menunggu', 'lunas' => 'Lunas', 'gagal' => 'Gagal', 'kedaluwarsa' => 'Kedaluwarsa', 'dibatalkan' => 'Dibatalkan'],
                                default => ['draft' => 'Draf', 'submitted' => 'Menunggu', 'accepted' => 'Diterima', 'rejected' => 'Ditolak'],
                            } as $value => $statusLabel)
                                <option value="{{ $value }}">{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="flex items-end"><button class="min-h-10 w-full rounded-lg bg-muhammadiyah-hijau px-4 font-semibold text-white">Unduh {{ $label }}</button></div>
            </form>
        @endforeach
    </main>
</x-app-layout>
