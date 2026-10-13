<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Permohonan izin dan sakit</h1></x-slot>
    <main class="mx-auto max-w-6xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<p role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</p>@endif
        @if ($canApply)
            <section class="rounded-xl bg-white p-5 shadow-sm">
                @if (auth()->user()->role === \App\Models\User::ROLE_SISWA)
                    <a href="{{ route('siswa.excuses.create') }}" class="inline-flex min-h-11 items-center rounded-lg bg-muhammadiyah-hijau px-5 font-bold text-white">Ajukan izin/sakit</a>
                @else
                    <h2 class="font-bold">Pilih anak untuk pengajuan</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($children as $child)
                            <a href="{{ route('orang_tua.children.excuses.create', $child) }}" class="inline-flex min-h-11 items-center rounded-lg bg-muhammadiyah-hijau px-4 font-semibold text-white">{{ $child->name }}</a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <ul class="divide-y divide-gray-100">
                @forelse ($requests as $excuse)
                    <li class="space-y-3 p-4 sm:p-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="font-bold text-gray-900">{{ $excuse->student->user->name }} · {{ ucfirst($excuse->type) }} · {{ $excuse->attendance_date->format('d/m/Y') }}</p>
                                <p class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $excuse->reason }}</p>
                                <p class="mt-1 text-sm">Pengaju: {{ $excuse->requester->name }} · Status: <strong>{{ ucfirst($excuse->status) }}</strong></p>
                                @if ($excuse->review_note)<p class="mt-1 text-sm text-gray-700">Catatan: {{ $excuse->review_note }}</p>@endif
                                @if ($excuse->attachment_path)
                                    <a href="{{ route('excuses.attachment', $excuse) }}" class="mt-2 inline-block font-semibold text-muhammadiyah-hijau-tua underline">Unduh lampiran privat</a>
                                @endif
                            </div>
                            @if (in_array(auth()->user()->role, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_GURU], true) && $excuse->status === \App\Models\ExcuseRequest::STATUS_PENDING)
                                <form method="POST" action="{{ route(auth()->user()->role === \App\Models\User::ROLE_ADMIN ? 'admin.excuses.review' : 'guru.excuses.review', $excuse) }}" class="w-full space-y-2 sm:max-w-sm">
                                    @csrf @method('PUT')
                                    <label class="block text-sm font-semibold" for="decision-{{ $excuse->id }}">Keputusan</label>
                                    <select id="decision-{{ $excuse->id }}" name="decision" required class="min-h-11 w-full rounded-lg border-gray-300">
                                        <option value="">Pilih</option><option value="disetujui">Setujui</option><option value="ditolak">Tolak</option>
                                    </select>
                                    <textarea name="review_note" required maxlength="2000" rows="2" placeholder="Catatan wajib" class="w-full rounded-lg border-gray-300"></textarea>
                                    <button class="min-h-11 w-full rounded-lg bg-muhammadiyah-hijau px-4 font-bold text-white">Simpan keputusan</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="p-8 text-center text-gray-600">Belum ada permohonan izin atau sakit.</li>
                @endforelse
            </ul>
            @if ($requests->hasPages())<div class="border-t p-4">{{ $requests->links() }}</div>@endif
        </section>
    </main>
</x-app-layout>
