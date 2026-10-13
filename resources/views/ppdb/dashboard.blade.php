<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-white">Pendaftaran PPDB</h1>
    </x-slot>
    <main class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))
            <div role="status" class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-900">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-muhammadiyah-hijau-tua">Status pendaftaran</h2>
                    <p class="mt-2">Nomor pendaftaran: <strong>{{ $application->registration_number ?? 'Belum dikirim' }}</strong></p>
                    <p class="mt-1">Status: <strong>{{ match ($application->status) { 'draft' => 'Draf', 'submitted' => 'Menunggu verifikasi', 'accepted' => 'Diterima', 'rejected' => 'Ditolak', default => $application->status } }}</strong></p>
                    @if ($application->decision_reason)
                        <p class="mt-2 rounded-lg bg-amber-50 p-3 text-amber-900">Catatan keputusan: {{ $application->decision_reason }}</p>
                    @endif
                </div>
                @if ($application->registration_number)
                    <a href="{{ route('pendaftar.receipt') }}" class="inline-flex min-h-11 items-center rounded-lg bg-muhammadiyah-hijau px-4 py-2 font-semibold text-white hover:bg-muhammadiyah-hijau-tua">Unduh bukti PDF</a>
                @endif
            </div>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">1. Data calon murid</h2>
            @if ($application->status === 'draft' || $document->status === 'rejected')
                <form method="POST" action="{{ route('pendaftar.student.update') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                    @csrf @method('PUT')
                    <div class="sm:col-span-2"><label for="student_name" class="block font-semibold">Nama lengkap</label><input id="student_name" name="student_name" value="{{ old('student_name', $application->student_name) }}" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                    <div><label for="nisn" class="block font-semibold">NISN</label><input id="nisn" name="nisn" inputmode="numeric" maxlength="10" value="{{ old('nisn', $application->nisn) }}" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                    <div><label for="school_level" class="block font-semibold">Jenjang</label><select id="school_level" name="school_level" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300"><option value="">Pilih jenjang</option><option value="SMP" @selected(old('school_level', $application->school_level) === 'SMP')>SMP</option><option value="SMA" @selected(old('school_level', $application->school_level) === 'SMA')>SMA</option></select></div>
                    <div><label for="major" class="block font-semibold">Jurusan (khusus SMA)</label><input id="major" name="major" value="{{ old('major', $application->major) }}" maxlength="120" class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                    <div><label for="birth_place" class="block font-semibold">Tempat lahir</label><input id="birth_place" name="birth_place" value="{{ old('birth_place', $application->birth_place) }}" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                    <div><label for="birth_date" class="block font-semibold">Tanggal lahir</label><input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $application->birth_date?->format('Y-m-d')) }}" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                    <div><label for="previous_school" class="block font-semibold">Asal sekolah</label><input id="previous_school" name="previous_school" value="{{ old('previous_school', $application->previous_school) }}" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                    <div class="sm:col-span-2"><label for="address" class="block font-semibold">Alamat</label><textarea id="address" name="address" rows="3" required class="mt-1 w-full rounded-lg border-gray-300">{{ old('address', $application->address) }}</textarea></div>
                    <div class="sm:col-span-2"><button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white">Simpan data murid</button></div>
                </form>
            @else
                <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach (['Nama' => $application->student_name, 'NISN' => $application->nisn, 'Jenjang' => $application->school_level, 'Jurusan' => $application->major, 'Tempat, tanggal lahir' => trim(($application->birth_place ?? '').', '.($application->birth_date?->format('d/m/Y') ?? ''), ', '), 'Asal sekolah' => $application->previous_school, 'Alamat' => $application->address] as $label => $value)
                        <div><dt class="text-sm text-gray-500">{{ $label }}</dt><dd class="font-medium">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            @endif
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">2. Data orang tua/wali</h2>
            @if ($application->status === 'draft' || $application->documents->where('category', 'lainnya')->contains('status', 'rejected'))
                <form method="POST" action="{{ route('pendaftar.guardian.update') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                    @csrf @method('PUT')
                    <div><label for="guardian_name" class="block font-semibold">Nama orang tua/wali</label><input id="guardian_name" name="guardian_name" value="{{ old('guardian_name', $application->guardian_name) }}" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                    <div><label for="guardian_relation" class="block font-semibold">Hubungan</label><input id="guardian_relation" name="guardian_relation" value="{{ old('guardian_relation', $application->guardian_relation) }}" placeholder="Ayah, ibu, wali" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                    <div><label for="guardian_phone" class="block font-semibold">Nomor telepon</label><input id="guardian_phone" name="guardian_phone" value="{{ old('guardian_phone', $application->guardian_phone) }}" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                    <div><label for="guardian_email" class="block font-semibold">Email wali (opsional)</label><input id="guardian_email" type="email" name="guardian_email" value="{{ old('guardian_email', $application->guardian_email) }}" class="mt-1 min-h-11 w-full rounded-lg border-gray-300"></div>
                    <div class="sm:col-span-2"><button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white">Simpan data wali</button></div>
                </form>
            @else
                <p class="mt-3">{{ $application->guardian_name }} — {{ $application->guardian_relation }} — {{ $application->guardian_phone }}</p>
            @endif
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">3. Unggah berkas</h2>
            <p class="mt-2 text-sm text-gray-600">PDF, JPG, atau PNG; maksimal 2 MB per berkas. Berkas disimpan privat dan hanya dapat diunduh melalui akun berotorisasi.</p>
            <div class="mt-4 space-y-3">
                @foreach (['kk' => 'Kartu Keluarga', 'akta' => 'Akta kelahiran', 'ijazah_rapor' => 'Ijazah/rapor', 'pas_foto' => 'Pas foto'] as $category => $label)
                    @php($document = $application->documents->where('category', $category)->sortByDesc('id')->first())
                    <div class="rounded-lg border border-gray-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-semibold">{{ $label }}: {{ $document ? match ($document->status) { 'approved' => 'Disetujui', 'rejected' => 'Ditolak', default => 'Menunggu' } : 'Belum diunggah' }}</p>
                            @if ($document)<a class="font-semibold text-muhammadiyah-hijau-tua underline" href="{{ route('ppdb.documents.download', $document) }}">Unduh berkas</a>@endif
                        </div>
                        @if ($document?->review_reason)<p class="mt-2 text-sm text-red-700">Alasan penolakan: {{ $document->review_reason }}</p>@endif
                        @if (in_array($application->status, ['draft', 'submitted'], true) && (! $document || $document->status === 'rejected'))
                            <form method="POST" action="{{ route('pendaftar.documents.upload') }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-3">
                                @csrf
                                <label class="sr-only" for="upload-{{ $category }}">Unggah {{ $label }}</label>
                                <input id="upload-{{ $category }}" type="file" name="{{ $category }}" accept=".pdf,.jpg,.jpeg,.png" required class="max-w-full text-sm">
                                <button class="min-h-10 rounded-lg border border-muhammadiyah-hijau px-4 font-semibold text-muhammadiyah-hijau-tua">Unggah</button>
                            </form>
                        @endif
                    </div>
                @endforeach
                @foreach ($application->documents->where('category', 'lainnya') as $document)
                    <div class="flex flex-wrap justify-between gap-2 rounded-lg border border-gray-200 p-4">
                        <div>
                            <span>{{ $document->original_name }} — {{ match ($document->status) { 'approved' => 'Disetujui', 'rejected' => 'Ditolak', default => 'Menunggu' } }}</span>
                            @if ($document->review_reason)<p class="mt-1 text-sm text-red-700">Alasan penolakan: {{ $document->review_reason }}</p>@endif
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <a class="font-semibold text-muhammadiyah-hijau-tua underline" href="{{ route('ppdb.documents.download', $document) }}">Unduh</a>
                            @if ($document->status === 'rejected' && in_array($application->status, ['draft', 'submitted'], true))
                                <form method="POST" action="{{ route('pendaftar.documents.upload') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <label class="sr-only" for="upload-other-{{ $document->id }}">Unggah ulang {{ $document->original_name }}</label>
                                    <input id="upload-other-{{ $document->id }}" type="file" name="lainnya_ulang[{{ $document->id }}]" accept=".pdf,.jpg,.jpeg,.png" required class="max-w-full text-sm">
                                    <button class="min-h-10 rounded-lg border border-muhammadiyah-hijau px-3 font-semibold text-muhammadiyah-hijau-tua">Unggah ulang</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
                @if ($application->status === 'draft')
                    <form method="POST" action="{{ route('pendaftar.documents.upload') }}" enctype="multipart/form-data" class="rounded-lg border border-dashed border-gray-300 p-4">
                        @csrf
                        <label for="upload-lainnya" class="block font-semibold">Berkas lain (opsional)</label>
                        <input id="upload-lainnya" type="file" name="lainnya[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="mt-2 max-w-full text-sm">
                        <button class="mt-3 min-h-10 rounded-lg border border-muhammadiyah-hijau px-4 font-semibold text-muhammadiyah-hijau-tua">Unggah berkas lain</button>
                    </form>
                @endif
            </div>
        </section>

        @if ($application->status === 'draft')
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                <h2 class="font-bold text-amber-950">Periksa kembali sebelum mengirim</h2>
                <p class="mt-2 text-sm text-amber-900">Setelah dikirim, data pendaftaran terkunci untuk menjaga proses verifikasi. Berkas yang ditolak dapat diunggah ulang sesuai catatan admin.</p>
                <form method="POST" action="{{ route('pendaftar.submit') }}" class="mt-4">
                    @csrf
                    <button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-bold text-white">Kirim pendaftaran</button>
                </form>
            </section>
        @endif
    </main>
</x-app-layout>
