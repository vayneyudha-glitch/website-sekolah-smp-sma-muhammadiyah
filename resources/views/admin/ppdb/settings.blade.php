<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Pengaturan informasi PPDB</h1></x-slot>
    <main class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('admin.ppdb.settings.update') }}" class="space-y-5 rounded-xl bg-white p-5 shadow-sm sm:p-7">
            @csrf @method('PUT')
            <label class="flex items-center gap-3 font-semibold"><input type="checkbox" name="is_open" value="1" @checked(old('is_open', $settings->is_open)) class="rounded border-gray-300 text-muhammadiyah-hijau focus:ring-muhammadiyah-hijau"> Buka pendaftaran PPDB</label>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="starts_on" class="block font-semibold">Tanggal mulai</label><input id="starts_on" type="date" name="starts_on" value="{{ old('starts_on', $settings->starts_on?->format('Y-m-d')) }}" class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                <div><label for="ends_on" class="block font-semibold">Tanggal akhir</label><input id="ends_on" type="date" name="ends_on" value="{{ old('ends_on', $settings->ends_on?->format('Y-m-d')) }}" class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
            </div>
            <div><label for="wave_schedule" class="block font-semibold">Jadwal gelombang</label><textarea id="wave_schedule" name="wave_schedule" rows="5" required class="mt-1 w-full rounded-lg border-gray-300">{{ old('wave_schedule', $settings->wave_schedule) }}</textarea></div>
            <div><label for="registration_flow" class="block font-semibold">Alur pendaftaran</label><textarea id="registration_flow" name="registration_flow" rows="5" required class="mt-1 w-full rounded-lg border-gray-300">{{ old('registration_flow', $settings->registration_flow) }}</textarea></div>
            <div><label for="costs" class="block font-semibold">Informasi biaya</label><textarea id="costs" name="costs" rows="5" required class="mt-1 w-full rounded-lg border-gray-300">{{ old('costs', $settings->costs) }}</textarea></div>
            <div class="flex flex-wrap gap-3"><button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white">Simpan pengaturan</button><a href="{{ route('admin.ppdb.index') }}" class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 px-5 font-semibold">Daftar pendaftar</a></div>
        </form>
    </main>
</x-app-layout>
