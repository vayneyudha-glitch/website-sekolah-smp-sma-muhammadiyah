<x-layouts.public :title="$announcement->title">
    <article class="mx-auto my-8 max-w-4xl rounded-xl bg-white p-5 shadow-sm sm:p-8">
        <a href="{{ route('public.announcements') }}" class="font-semibold text-muhammadiyah-hijau-tua underline">Kembali ke berita</a>
        <p class="mt-6 text-sm text-gray-600">{{ $announcement->published_at->format('d/m/Y') }}</p>
        <p class="mt-1 text-sm font-semibold text-muhammadiyah-hijau">{{ $announcement->category }}</p>
        <h1 class="mt-2 text-3xl font-bold leading-tight text-muhammadiyah-hijau-tua">{{ $announcement->title }}</h1>
        @if ($announcement->image_path)
            <img src="{{ asset('storage/'.$announcement->image_path) }}" alt="Gambar: {{ $announcement->title }}" class="mt-6 max-h-[32rem] w-full rounded-lg object-cover">
        @endif
        <div class="mt-6 whitespace-pre-line break-words text-base leading-8 text-gray-800">{{ $announcement->body }}</div>
    </article>
</x-layouts.public>
