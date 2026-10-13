<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-white">Pemeriksaan pendaftaran {{ $application->registration_number }}</h1>
    </x-slot>
    <main class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Data pendaftar</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ([
                    'Nama' => $application->student_name,
                    'Email akun' => $application->user->email,
                    'NISN' => $application->nisn,
                    'Jenjang' => $application->school_level,
                    'Jurusan' => $application->major,
                    'Tempat, tanggal lahir' => trim(($application->birth_place ?? '').', '.($application->birth_date?->format('d/m/Y') ?? ''), ', '),
                    'Alamat' => $application->address,
                    'Asal sekolah' => $application->previous_school,
                    'Orang tua/wali' => $application->guardian_name,
                    'Hubungan / telepon' => trim(($application->guardian_relation ?? '').' / '.($application->guardian_phone ?? ''), ' /'),
                    'Email wali' => $application->guardian_email,
                    'Kelas' => $application->schoolClass?->name,
                ] as $label => $value)
                    <div><dt class="text-sm text-gray-500">{{ $label }}</dt><dd class="font-medium">{{ $value ?: '—' }}</dd></div>
                @endforeach
            </dl>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Verifikasi berkas</h2>
            <div class="mt-4 space-y-4">
                @forelse ($application->documents as $document)
                    <article class="rounded-lg border border-gray-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div><h3 class="font-semibold">{{ match ($document->category) { 'kk' => 'Kartu Keluarga', 'akta' => 'Akta kelahiran', 'ijazah_rapor' => 'Ijazah/rapor', 'pas_foto' => 'Pas foto', default => 'Berkas lain' }} — {{ $document->original_name }}</h3><p class="mt-1 text-sm">Status: {{ match ($document->status) { 'approved' => 'Disetujui', 'rejected' => 'Ditolak', default => 'Menunggu' } }}@if ($document->review_reason) — {{ $document->review_reason }}@endif</p></div>
                            <a href="{{ route('ppdb.documents.download', $document) }}" class="font-semibold text-muhammadiyah-hijau-tua underline">Unduh berkas (tercatat)</a>
                        </div>
                        @if ($application->status === 'submitted')
                            <form method="POST" action="{{ route('admin.ppdb.documents.review', $document) }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_2fr_auto]">
                                @csrf @method('PUT')
                                <div><label for="status-{{ $document->id }}" class="sr-only">Status berkas</label><select id="status-{{ $document->id }}" name="status" required class="min-h-10 w-full rounded-lg border-gray-300"><option value="approved">Disetujui</option><option value="rejected" @selected($document->status === 'rejected')>Ditolak</option></select></div>
                                <div><label for="reason-{{ $document->id }}" class="sr-only">Alasan jika ditolak</label><input id="reason-{{ $document->id }}" name="review_reason" value="{{ old('review_reason') }}" placeholder="Alasan jika ditolak" class="min-h-10 w-full rounded-lg border-gray-300"></div>
                                <button class="min-h-10 rounded-lg bg-muhammadiyah-hijau px-4 font-semibold text-white">Simpan</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="text-gray-600">Belum ada dokumen diunggah.</p>
                @endforelse
            </div>
        </section>

        @if ($application->status === 'submitted')
            <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
                <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Keputusan akhir</h2>
                <form method="POST" action="{{ route('admin.ppdb.decide', $application) }}" class="mt-4 space-y-4">
                    @csrf @method('PUT')
                    <div><label for="decision" class="block font-semibold">Keputusan</label><select id="decision" name="decision" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300"><option value="accepted">Diterima</option><option value="rejected">Ditolak</option></select></div>
                    <div><label for="school_class_id" class="block font-semibold">Kelas (wajib jika diterima)</label><select id="school_class_id" name="school_class_id" class="mt-1 min-h-11 w-full rounded-lg border-gray-300"><option value="">Pilih kelas jenjang {{ $application->school_level }}</option>@foreach ($classes as $class)<option value="{{ $class->id }}">{{ $class->academicYear->name }} — {{ $class->name }}</option>@endforeach</select></div>
                    <div><label for="decision_reason" class="block font-semibold">Alasan keputusan (wajib jika ditolak)</label><textarea id="decision_reason" name="decision_reason" rows="3" class="mt-1 w-full rounded-lg border-gray-300"></textarea></div>
                    <button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white">Simpan keputusan</button>
                </form>
            </section>
        @elseif ($application->status === 'accepted' || $application->status === 'rejected')
            <section class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="font-bold">Keputusan: {{ $application->status === 'accepted' ? 'Diterima' : 'Ditolak' }}</h2>
                @if ($application->decision_reason)<p class="mt-2">{{ $application->decision_reason }}</p>@endif
            </section>
        @endif
        <a href="{{ route('admin.ppdb.index') }}" class="inline-flex min-h-10 items-center font-semibold text-muhammadiyah-hijau-tua underline">Kembali ke daftar pendaftar</a>
    </main>
</x-app-layout>
