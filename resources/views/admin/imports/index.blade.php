<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Impor data sekolah</h1></x-slot>
    <main class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))
            <p role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</p>
        @endif
        @if ($passwordExportBatch)
            <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-950">
                Kata sandi sementara tersedia hanya sekali. Unduh dan simpan secara aman sekarang.
                <a class="ml-2 font-bold underline" href="{{ route('admin.imports.passwords', $passwordExportBatch) }}">Unduh daftar akun sementara (CSV)</a>
            </section>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif
        <section class="grid gap-4 sm:grid-cols-2">
            @foreach ($templates as $type => $columns)
                <article class="rounded-xl bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-gray-900">{{ ['students' => 'Murid', 'teachers' => 'Guru', 'parents' => 'Orang tua', 'classes' => 'Kelas'][$type] }}</h2>
                    <p class="mt-2 text-sm text-gray-700">Kolom templat: <span class="font-mono">{{ implode(', ', $columns) }}</span></p>
                    <a href="{{ route('admin.imports.template', $type) }}" class="mt-3 inline-flex min-h-11 items-center rounded-lg border border-muhammadiyah-hijau px-4 font-semibold text-muhammadiyah-hijau-tua">Unduh templat Excel</a>
                </article>
            @endforeach
        </section>
        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-lg font-bold text-gray-900">Unggah dan pratinjau</h2>
            <p class="mt-1 text-sm text-gray-700">Maksimal 10 MB dan 2.000 baris. Data belum disimpan sampai Anda mengonfirmasi pratinjau.</p>
            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.imports.preview.store') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="type" class="mb-1 block font-semibold">Jenis data</label>
                    <select id="type" name="type" required class="min-h-12 w-full rounded-lg border-gray-300 sm:max-w-md">
                        <option value="">Pilih jenis</option>
                        <option value="students">Murid</option><option value="teachers">Guru</option>
                        <option value="parents">Orang tua</option><option value="classes">Kelas</option>
                    </select>
                </div>
                <div>
                    <label for="file" class="mb-1 block font-semibold">Berkas Excel</label>
                    <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv" required class="block min-h-12 w-full rounded-lg border border-gray-300 p-2 sm:max-w-xl">
                </div>
                <button class="min-h-12 rounded-lg bg-muhammadiyah-hijau px-6 font-bold text-white">Unggah untuk pratinjau</button>
            </form>
        </section>
    </main>
</x-app-layout>
