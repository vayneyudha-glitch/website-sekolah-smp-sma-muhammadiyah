<x-layouts.public :title="'Daftar Guru'">
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <p class="font-semibold text-muhammadiyah-hijau">Tenaga pendidik</p>
            <h1 class="mt-1 text-3xl font-bold text-muhammadiyah-hijau-tua">Daftar Guru</h1>
            <p class="mt-2 text-gray-700">Kenali guru yang mendampingi kegiatan belajar di sekolah.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($teachers as $teacher)
                <article class="rounded-xl bg-white p-5 shadow-sm">
                    @if ($teacher->photo_path)
                        <img src="{{ asset('storage/'.$teacher->photo_path) }}" alt="Foto {{ $teacher->user->name }}" class="mb-4 h-24 w-24 rounded-full object-cover">
                    @else
                        <div class="mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-muhammadiyah-hijau-muda text-2xl font-bold text-muhammadiyah-hijau-tua" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($teacher->user->name, 0, 1)) }}</div>
                    @endif
                    <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">{{ $teacher->user->name }}</h2>
                    @if ($teacher->bio)<p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $teacher->bio }}</p>@endif
                    <p class="mt-3 text-sm font-semibold text-gray-600">Mata pelajaran</p>
                    <ul class="mt-1 flex flex-wrap gap-2">
                        @forelse ($teacher->subjects as $subject)
                            <li class="rounded-full bg-muhammadiyah-hijau-muda px-3 py-1 text-sm text-muhammadiyah-hijau-tua">{{ $subject->name }}</li>
                        @empty
                            <li class="text-sm text-gray-600">Belum ada mata pelajaran yang ditampilkan.</li>
                        @endforelse
                    </ul>
                    <p class="mt-3 text-sm font-semibold text-gray-600">Kelas yang diampu</p>
                    <ul class="mt-1 flex flex-wrap gap-2">
                        @forelse ($teacher->classes->merge($teacher->homeroomClasses)->unique('id') as $class)
                            <li class="rounded-full bg-muhammadiyah-hijau-muda px-3 py-1 text-sm text-muhammadiyah-hijau-tua">{{ $class->school_level }} {{ $class->grade }} {{ $class->name }}</li>
                        @empty
                            <li class="text-sm text-gray-600">Belum ada kelas yang ditampilkan.</li>
                        @endforelse
                    </ul>
                </article>
            @empty
                <p class="rounded-xl bg-white p-6 text-gray-700">Informasi guru belum tersedia.</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $teachers->links() }}</div>
    </section>
</x-layouts.public>
