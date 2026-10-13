<x-layouts.public :title="'Pengumuman dan Berita'">
    <section class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <p class="font-semibold text-muhammadiyah-hijau">Informasi sekolah</p>
        <h1 class="mt-1 text-3xl font-bold text-muhammadiyah-hijau-tua">Pengumuman dan Berita</h1>
        <div class="mt-6 space-y-4">
            @forelse ($announcements as $announcement)
                <article class="rounded-xl bg-white p-5 shadow-sm">
                    @if ($announcement->image_path)
                        <img src="{{ asset('storage/'.$announcement->image_path) }}" alt="" class="mb-4 max-h-64 w-full rounded-lg object-cover">
                    @endif
                    <p class="text-xs font-semibold uppercase tracking-wide text-muhammadiyah-hijau">{{ $announcement->category }}</p>
                    <p class="text-sm text-gray-600">{{ $announcement->published_at->format('d/m/Y') }}</p>
                    <h2 class="mt-1 text-xl font-bold text-muhammadiyah-hijau-tua">
                        <a class="hover:underline" href="{{ route('public.announcement', ['announcement' => $announcement->slug]) }}">{{ $announcement->title }}</a>
                    </h2>
                    <p class="mt-2 line-clamp-3 whitespace-pre-line leading-7 text-gray-700">{{ \Illuminate\Support\Str::limit($announcement->body, 240) }}</p>
                    <a class="mt-3 inline-flex min-h-10 items-center font-semibold text-muhammadiyah-hijau-tua underline" href="{{ route('public.announcement', ['announcement' => $announcement->slug]) }}">Baca selengkapnya</a>
                </article>
            @empty
                <p class="rounded-xl bg-white p-6 text-gray-700">Belum ada pengumuman atau berita yang ditayangkan.</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $announcements->links() }}</div>
    </section>
</x-layouts.public>
