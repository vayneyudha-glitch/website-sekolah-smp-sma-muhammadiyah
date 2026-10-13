<x-layouts.public title="Galeri" description="Galeri foto sekolah.">
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <h1 class="text-3xl font-bold text-muhammadiyah-hijau-tua">Galeri Sekolah</h1>
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($galleries as $item)
                <figure class="overflow-hidden rounded-xl bg-white shadow-sm">
                    <img src="{{ asset('storage/'.$item->photo_path) }}" alt="{{ $item->title }}" loading="lazy" class="aspect-[4/3] w-full object-cover">
                    <figcaption class="flex items-center justify-between gap-3 p-4">
                        <span class="font-semibold text-gray-900">{{ $item->title }}</span>
                        @if ($item->school_level)<span class="shrink-0 text-sm text-muhammadiyah-hijau-tua">{{ $item->school_level }}</span>@endif
                    </figcaption>
                </figure>
            @empty
                <p class="rounded-xl bg-white p-6 text-gray-700">Belum ada foto galeri.</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $galleries->links() }}</div>
    </main>
</x-layouts.public>
