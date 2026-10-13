<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Kelola Galeri Sekolah</h1></x-slot>
    <main class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">Periksa kembali isian yang ditandai.</div>@endif
        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.school-content.gallery.store') }}" class="grid gap-4 rounded-xl bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
            @csrf
            <div><label for="title" class="block font-semibold">Judul foto</label><input id="title" name="title" required maxlength="255" value="{{ old('title') }}" class="mt-1 min-h-12 w-full rounded-lg border-gray-300">@error('title')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div><label for="photo" class="block font-semibold">Foto (JPG, PNG, WEBP maks. 5 MB)</label><input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp" required class="mt-1 block min-h-12 w-full rounded-lg border border-gray-300 p-2">@error('photo')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div><label for="school_level" class="block font-semibold">Jenjang</label><select id="school_level" name="school_level" class="mt-1 min-h-12 w-full rounded-lg border-gray-300"><option value="">Semua jenjang</option><option value="SMP">SMP</option><option value="SMA">SMA</option></select></div>
            <button class="mt-auto inline-flex min-h-12 items-center justify-center rounded-lg bg-muhammadiyah-hijau px-5 font-bold text-white">Unggah foto</button>
        </form>
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($galleries as $item)
                <article class="overflow-hidden rounded-xl bg-white shadow-sm">
                    <img src="{{ asset('storage/'.$item->photo_path) }}" alt="{{ $item->title }}" class="aspect-[4/3] w-full object-cover">
                    <div class="flex items-center justify-between gap-3 p-4">
                        <div><h2 class="font-semibold text-gray-900">{{ $item->title }}</h2><p class="text-sm text-gray-600">{{ $item->school_level ?: 'Semua jenjang' }}</p></div>
                        <form method="POST" action="{{ route('admin.school-content.gallery.destroy', $item) }}" onsubmit="return confirm('Hapus foto ini?');">@csrf @method('DELETE')<button class="inline-flex min-h-10 items-center rounded-lg border border-red-200 px-3 font-semibold text-red-700">Hapus</button></form>
                    </div>
                </article>
            @empty
                <p class="rounded-xl bg-white p-6 text-gray-700">Belum ada foto galeri.</p>
            @endforelse
        </section>
        @if ($galleries->hasPages())<div>{{ $galleries->links() }}</div>@endif
        <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center font-semibold text-muhammadiyah-hijau-tua underline">Kembali ke dashboard admin</a>
    </main>
</x-app-layout>
