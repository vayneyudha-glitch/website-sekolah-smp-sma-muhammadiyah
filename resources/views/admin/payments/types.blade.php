<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-bold text-white">Jenis Tagihan</h1>
            <a href="{{ route('admin.payments.index') }}" class="inline-flex min-h-10 items-center text-white underline">Kembali ke pembayaran</a>
        </div>
    </x-slot>

    <main class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><p class="font-semibold">Periksa kembali data berikut.</p><ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Tambah jenis tagihan</h2>
            <form method="POST" action="{{ route('admin.payments.types.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @csrf
                <div><label for="academic_year_id" class="block text-sm font-semibold">Tahun ajaran</label><select id="academic_year_id" name="academic_year_id" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300">@foreach ($academicYears as $year)<option value="{{ $year->id }}" @selected(old('academic_year_id') == $year->id)>{{ $year->name }}</option>@endforeach</select></div>
                <div><label for="name" class="block text-sm font-semibold">Nama tagihan</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="120" class="mt-1 min-h-11 w-full rounded-lg border-gray-300" placeholder="SPP Bulanan"></div>
                <div><label for="amount" class="block text-sm font-semibold">Nominal (rupiah)</label><input id="amount" name="amount" type="number" min="1" max="1000000000" value="{{ old('amount') }}" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                <div><label for="school_level" class="block text-sm font-semibold">Jenjang sasaran (opsional)</label><select id="school_level" name="school_level" class="mt-1 min-h-11 w-full rounded-lg border-gray-300"><option value="">Semua jenjang</option><option value="SMP" @selected(old('school_level') === 'SMP')>SMP</option><option value="SMA" @selected(old('school_level') === 'SMA')>SMA</option></select></div>
                <div><label for="grade" class="block text-sm font-semibold">Kelas/tingkat sasaran (opsional)</label><input id="grade" name="grade" value="{{ old('grade') }}" maxlength="30" class="mt-1 min-h-11 w-full rounded-lg border-gray-300" placeholder="7 atau X"></div>
                <div><label for="due_date" class="block text-sm font-semibold">Jatuh tempo</label><input id="due_date" name="due_date" type="date" value="{{ old('due_date') }}" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                <div class="sm:col-span-2 lg:col-span-3"><button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-bold text-white hover:bg-muhammadiyah-hijau-tua">Simpan jenis tagihan</button></div>
            </form>
        </section>

        <section class="space-y-4">
            @forelse ($types as $type)
                <article class="rounded-xl bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">{{ $type->name }} <span class="text-sm font-normal text-gray-600">· {{ $type->academicYear->name }} · Rp{{ number_format($type->amount, 0, ',', '.') }}</span></h2>
                        <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $type->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $type->is_active ? 'Aktif' : 'Tidak aktif' }}</span>
                    </div>
                    <form method="POST" action="{{ route('admin.payments.types.update', $type) }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @csrf @method('PUT')
                        <div><label class="block text-sm font-semibold">Tahun ajaran</label><select name="academic_year_id" required class="mt-1 min-h-10 w-full rounded-lg border-gray-300">@foreach ($academicYears as $year)<option value="{{ $year->id }}" @selected($type->academic_year_id === $year->id)>{{ $year->name }}</option>@endforeach</select></div>
                        <div><label class="block text-sm font-semibold">Nama</label><input name="name" value="{{ $type->name }}" required maxlength="120" class="mt-1 min-h-10 w-full rounded-lg border-gray-300"></div>
                        <div><label class="block text-sm font-semibold">Nominal (rupiah)</label><input name="amount" type="number" min="1" max="1000000000" value="{{ $type->amount }}" required class="mt-1 min-h-10 w-full rounded-lg border-gray-300"></div>
                        <div><label class="block text-sm font-semibold">Jenjang sasaran</label><select name="school_level" class="mt-1 min-h-10 w-full rounded-lg border-gray-300"><option value="">Semua jenjang</option><option value="SMP" @selected($type->school_level === 'SMP')>SMP</option><option value="SMA" @selected($type->school_level === 'SMA')>SMA</option></select></div>
                        <div><label class="block text-sm font-semibold">Kelas/tingkat sasaran</label><input name="grade" value="{{ $type->grade }}" maxlength="30" class="mt-1 min-h-10 w-full rounded-lg border-gray-300"></div>
                        <div><label class="block text-sm font-semibold">Jatuh tempo</label><input name="due_date" type="date" value="{{ $type->due_date->format('Y-m-d') }}" required class="mt-1 min-h-10 w-full rounded-lg border-gray-300"></div>
                        <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-3"><button class="min-h-10 rounded-lg bg-muhammadiyah-hijau px-4 font-semibold text-white">Simpan perubahan</button></div>
                    </form>
                    @if ($type->is_active)
                        <form method="POST" action="{{ route('admin.payments.types.archive', $type) }}" class="mt-2" onsubmit="return confirm('Nonaktifkan jenis tagihan ini? Riwayat yang sudah terbit tetap disimpan.');">
                            @csrf @method('DELETE')
                            <button class="min-h-10 rounded-lg border border-red-300 px-4 font-semibold text-red-700">Nonaktifkan</button>
                        </form>
                    @endif
                </article>
            @empty
                <div class="rounded-xl bg-white p-8 text-center text-gray-600">Belum ada jenis tagihan.</div>
            @endforelse
            @if ($types->hasPages())<div>{{ $types->links() }}</div>@endif
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Terbitkan tagihan massal</h2>
            <p class="mt-1 text-sm text-gray-600">Tagihan yang sudah pernah diterbitkan pada periode yang sama akan dilewati.</p>
            <form method="POST" action="{{ route('admin.payments.issue') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <div class="sm:col-span-2"><label for="issue_bill_type_id" class="block text-sm font-semibold">Jenis tagihan aktif</label><select id="issue_bill_type_id" name="bill_type_id" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300">@foreach ($activeTypes as $type)<option value="{{ $type->id }}">{{ $type->name }} · {{ $type->academicYear->name }}</option>@endforeach</select></div>
                <div><label for="billing_period" class="block text-sm font-semibold">Periode tagihan</label><input id="billing_period" name="billing_period" type="month" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                <div><label for="school_class_id" class="block text-sm font-semibold">Kelas tertentu (opsional)</label><select id="school_class_id" name="school_class_id" class="mt-1 min-h-11 w-full rounded-lg border-gray-300"><option value="">Pilih jenjang di bawah</option>@foreach ($classes as $class)<option value="{{ $class->id }}">{{ $class->school_level }} {{ $class->grade }} {{ $class->name }} · {{ $class->academicYear->name }}</option>@endforeach</select></div>
                <div><label for="issue_school_level" class="block text-sm font-semibold">Atau jenjang</label><select id="issue_school_level" name="school_level" class="mt-1 min-h-11 w-full rounded-lg border-gray-300"><option value="">Pilih kelas tertentu</option><option value="SMP">SMP</option><option value="SMA">SMA</option></select></div>
                <div><label for="issue_grade" class="block text-sm font-semibold">Tingkat (opsional)</label><input id="issue_grade" name="grade" class="mt-1 min-h-11 w-full rounded-lg border-gray-300" placeholder="7 atau X"></div>
                <div class="flex items-end sm:col-span-2"><button class="min-h-11 rounded-lg bg-muhammadiyah-kuning px-5 font-bold text-muhammadiyah-hijau-tua">Terbitkan untuk siswa sasaran</button></div>
            </form>
        </section>
    </main>
</x-app-layout>
