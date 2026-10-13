<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Ajukan izin atau sakit</h1></x-slot>
    <main class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
            <p class="mb-5 text-sm text-gray-700">Murid: <strong>{{ $student->user->name }}</strong>. Lampiran hanya dapat diunduh oleh pengaju dan petugas yang berwenang.</p>
            @if ($errors->any())<div role="alert" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            @php
                $parent = auth()->user()->role === \App\Models\User::ROLE_ORANG_TUA;
                $action = $parent
                    ? route('orang_tua.children.excuses.store', $student->user_id)
                    : route('siswa.excuses.store');
                $back = $parent ? route('orang_tua.excuses.index') : route('siswa.excuses.index');
            @endphp
            <form method="POST" enctype="multipart/form-data" action="{{ $action }}" class="space-y-5">
                @csrf
                <div>
                    <label for="attendance_date" class="mb-1 block font-semibold">Tanggal izin/sakit</label>
                    <input id="attendance_date" name="attendance_date" type="date" max="{{ today()->toDateString() }}" required value="{{ old('attendance_date') }}" class="min-h-12 w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label for="type" class="mb-1 block font-semibold">Jenis</label>
                    <select id="type" name="type" required class="min-h-12 w-full rounded-lg border-gray-300"><option value="">Pilih jenis</option><option value="izin">Izin</option><option value="sakit">Sakit</option></select>
                </div>
                <div>
                    <label for="reason" class="mb-1 block font-semibold">Alasan</label>
                    <textarea id="reason" name="reason" required maxlength="2000" rows="4" class="w-full rounded-lg border-gray-300">{{ old('reason') }}</textarea>
                </div>
                <div>
                    <label for="attachment" class="mb-1 block font-semibold">Lampiran surat (opsional)</label>
                    <input id="attachment" name="attachment" type="file" accept=".jpg,.jpeg,.png,.pdf" class="block min-h-12 w-full rounded-lg border border-gray-300 p-2">
                    <p class="mt-1 text-sm text-gray-600">JPG, PNG, atau PDF; maksimal 5 MB.</p>
                </div>
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                    <a href="{{ $back }}" class="inline-flex min-h-12 items-center justify-center rounded-lg border px-5 font-semibold">Batal</a>
                    <button class="min-h-12 rounded-lg bg-muhammadiyah-hijau px-6 font-bold text-white">Kirim permohonan</button>
                </div>
            </form>
        </section>
    </main>
</x-app-layout>
