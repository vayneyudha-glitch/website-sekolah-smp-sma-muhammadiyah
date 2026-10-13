<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Tinjauan Absensi</h1></x-slot>

    <main class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Penanda adalah sinyal untuk diperiksa, bukan bukti pasti kecurangan. Periksa konteks sebelum menyetujui atau menolak absensi.</p>
        @forelse ($items as $index => $item)
            <article class="space-y-4 rounded-xl bg-white p-5 shadow-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muhammadiyah-hijau-tua">{{ ucfirst($item['type']) }} · {{ $item['date']->format('d/m/Y') }}</p>
                    <h2 class="mt-1 text-lg font-bold">{{ $item['name'] }}</h2>
                    <p class="text-sm text-gray-600">{{ $item['summary'] }}</p>
                </div>
                <ul class="list-inside list-disc rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                    @foreach ($item['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach
                </ul>
                <form method="POST" action="{{ route('admin.attendance.review.update', [$item['type'], $item['id']]) }}" class="space-y-3">
                    @csrf @method('PUT')
                    <div>
                        <label for="review-note-{{ $index }}" class="block font-semibold">Catatan tinjauan (wajib)</label>
                        <textarea id="review-note-{{ $index }}" name="note" rows="2" maxlength="2000" required class="mt-1 w-full rounded-lg border-gray-300"></textarea>
                        <p class="mt-1 text-xs text-gray-600">Jangan masukkan kata sandi, token, NIK utuh, atau isi dokumen.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button name="decision" value="disetujui" class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white">Setujui</button>
                        <button name="decision" value="ditolak" class="min-h-11 rounded-lg border border-red-300 px-5 font-semibold text-red-800">Tolak</button>
                    </div>
                </form>
            </article>
        @empty
            <p class="rounded-xl bg-white p-8 text-center text-gray-600 shadow-sm">Tidak ada absensi yang menunggu tinjauan.</p>
        @endforelse
    </main>
</x-app-layout>
