<x-layouts.public title="Fasilitas" description="Informasi fasilitas sekolah.">
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <h1 class="text-3xl font-bold text-muhammadiyah-hijau-tua">Fasilitas Sekolah</h1>
        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($facilities as $facility)
                <article class="overflow-hidden rounded-xl bg-white shadow-sm">
                    @if ($facility->photo_path)
                        <img src="{{ asset('storage/'.$facility->photo_path) }}" alt="{{ $facility->name }}" class="h-52 w-full object-cover">
                    @endif
                    <div class="p-5">
                        @if ($facility->school_level)<p class="text-sm font-semibold text-muhammadiyah-hijau">{{ $facility->school_level }}</p>@endif
                        <h2 class="text-xl font-bold text-muhammadiyah-hijau-tua">{{ $facility->name }}</h2>
                        <p class="mt-2 whitespace-pre-line leading-7 text-gray-700">{{ $facility->description }}</p>
                    </div>
                </article>
            @empty
                <p class="rounded-xl bg-white p-6 text-gray-700">Informasi fasilitas belum diisi.</p>
            @endforelse
        </div>
    </main>
</x-layouts.public>
