<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">{{ $title }}</h2>
    </x-slot>

    <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
            <section aria-label="Ringkasan sekolah" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['label' => 'Murid', 'value' => number_format($adminSummary['students'])],
                    ['label' => 'Guru', 'value' => number_format($adminSummary['teachers'])],
                    ['label' => 'Kelas', 'value' => number_format($adminSummary['classes'])],
                    ['label' => 'Absensi murid hari ini', 'value' => number_format($adminSummary['attendance_today'])],
                ] as $card)
                    <article class="rounded-xl border border-green-100 bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold text-gray-600">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold text-muhammadiyah-hijau-tua">{{ $card['value'] }}</p>
                    </article>
                @endforeach
            </section>
            <section class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-xl bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Ringkasan PPDB</h2>
                    <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                        @foreach ([
                            ['Menunggu', $adminSummary['ppdb_waiting']],
                            ['Diterima', $adminSummary['ppdb_accepted']],
                            ['Ditolak', $adminSummary['ppdb_rejected']],
                        ] as [$label, $value])
                            <div class="rounded-lg bg-green-50 p-3">
                                <p class="text-xs text-gray-600">{{ $label }}</p>
                                <p class="mt-1 text-2xl font-bold text-muhammadiyah-hijau-tua">{{ number_format($value) }}</p>
                            </div>
                        @endforeach
                    </div>
                    <h3 class="mt-6 font-bold text-gray-800">Pembayaran bulan ini</h3>
                    <p class="mt-1 text-xl font-bold text-muhammadiyah-hijau-tua">Rp{{ number_format($adminSummary['payment_month'], 0, ',', '.') }}</p>
                    <p class="mt-3 text-sm text-gray-700">Tunggakan aktif: {{ number_format($adminSummary['unpaid_count']) }} tagihan, Rp{{ number_format($adminSummary['unpaid_amount'], 0, ',', '.') }}</p>
                </article>
                <article class="rounded-xl bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Pembayaran enam bulan terakhir</h2>
                    <div class="mt-5 flex h-44 items-end gap-2" role="img" aria-label="Grafik batang pembayaran lunas">
                        @php($chartMax = max(1, $paymentChart->max()))
                        @foreach ($paymentChart as $month => $amount)
                            <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2">
                                <span class="truncate text-center text-[10px] text-gray-600">Rp{{ number_format($amount / 1000000, 1, ',', '.') }} jt</span>
                                <div class="w-full rounded-t bg-muhammadiyah-hijau" style="height: {{ max(4, (int) round($amount / $chartMax * 100)) }}%"></div>
                                <span class="text-xs text-gray-600">{{ $month }}</span>
                            </div>
                        @endforeach
                    </div>
                </article>
            </section>
            <section class="flex flex-wrap gap-3" aria-label="Pintasan yang sering digunakan">
                <a href="{{ route('admin.data.index', ['resource' => 'students']) }}" class="rounded-lg bg-muhammadiyah-hijau px-4 py-3 font-semibold text-white">Data murid</a>
                <a href="{{ route('admin.ppdb.index') }}" class="rounded-lg border border-muhammadiyah-hijau px-4 py-3 font-semibold text-muhammadiyah-hijau-tua">PPDB</a>
                <a href="{{ route('admin.payments.index') }}" class="rounded-lg border border-muhammadiyah-hijau px-4 py-3 font-semibold text-muhammadiyah-hijau-tua">Pembayaran</a>
                <a href="{{ route('admin.excuses.index') }}" class="rounded-lg border border-muhammadiyah-hijau px-4 py-3 font-semibold text-muhammadiyah-hijau-tua">Izin/sakit</a>
                <a href="{{ route('admin.reports.index') }}" class="rounded-lg border border-muhammadiyah-hijau px-4 py-3 font-semibold text-muhammadiyah-hijau-tua">Nilai dan rapor</a>
            </section>
        @elseif (auth()->user()->role === \App\Models\User::ROLE_GURU)
            <section class="grid gap-6 lg:grid-cols-3">
                <article class="rounded-xl bg-white p-5 shadow-sm lg:col-span-2">
                    <h1 class="text-xl font-bold text-muhammadiyah-hijau-tua">Jadwal mengajar hari ini</h1>
                    <div class="mt-4 divide-y divide-gray-100">
                        @forelse ($todayTimetables as $lesson)
                            <p class="py-3 text-sm text-gray-700"><strong>{{ substr((string) $lesson->starts_at, 0, 5) }}–{{ substr((string) $lesson->ends_at, 0, 5) }}</strong> · {{ $lesson->schoolClass->name }} · {{ $lesson->subject->name }}</p>
                        @empty
                            <p class="py-4 text-sm text-gray-600">Tidak ada jadwal mengajar hari ini.</p>
                        @endforelse
                    </div>
                </article>
                <article class="space-y-4 rounded-xl bg-white p-5 shadow-sm">
                    <div>
                        <p class="text-sm text-gray-600">Kiriman tugas perlu dinilai</p>
                        <p class="text-3xl font-bold text-muhammadiyah-hijau-tua">{{ number_format($assignmentCounts['ungraded']) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Status absensi hari ini</p>
                        <p class="font-bold text-gray-900">{{ $ownAttendance?->check_in_at ? 'Sudah absen masuk' : 'Belum absen masuk' }}</p>
                    </div>
                    @if ($ownAttendance?->is_suspicious)
                        <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Absensi hari ini memerlukan verifikasi admin.</p>
                    @endif
                    <a href="{{ route('guru.assignments.index') }}" class="inline-flex min-h-11 items-center rounded-lg bg-muhammadiyah-hijau px-4 font-semibold text-white">Buka penilaian</a>
                </article>
            </section>
            <section class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Kelas yang diampu</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($teacherClasses as $schoolClass)
                        <span class="rounded-full bg-green-50 px-3 py-2 text-sm font-semibold text-muhammadiyah-hijau-tua">{{ $schoolClass->name }}</span>
                    @empty
                        <p class="text-sm text-gray-600">Belum ada kelas yang ditautkan ke akun ini.</p>
                    @endforelse
                </div>
                <a href="{{ route('guru.exports.index') }}" class="mt-4 inline-flex min-h-10 items-center rounded-lg border border-muhammadiyah-hijau px-4 font-semibold text-muhammadiyah-hijau-tua">Pusat ekspor kelas</a>
            </section>
        @elseif (auth()->user()->role === \App\Models\User::ROLE_SISWA)
            <section class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-xl bg-white p-5 shadow-sm">
                    <h1 class="text-xl font-bold text-muhammadiyah-hijau-tua">Jadwal hari ini</h1>
                    @forelse ($todayTimetables as $lesson)
                        <p class="mt-3 text-sm text-gray-700">{{ substr((string) $lesson->starts_at, 0, 5) }}–{{ substr((string) $lesson->ends_at, 0, 5) }} · {{ $lesson->subject->name }} · {{ $lesson->teacher->user->name }}</p>
                    @empty
                        <p class="mt-3 text-sm text-gray-600">Tidak ada jadwal hari ini.</p>
                    @endforelse
                </article>
                <article class="rounded-xl bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-bold text-muhammadiyah-hijau-tua">Tugas terdekat</h2>
                    @forelse ($upcomingAssignments as $assignment)
                        <p class="mt-3 text-sm text-gray-700"><strong>{{ $assignment->title }}</strong> · {{ $assignment->subject->name }} · {{ $assignment->due_at->format('d/m/Y H:i') }}</p>
                    @empty
                        <p class="mt-3 text-sm text-gray-600">Belum ada tugas mendatang.</p>
                    @endforelse
                </article>
                <article class="rounded-xl bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Nilai terbaru</h2>
                    @forelse ($latestGrades as $submission)
                        <p class="mt-3 text-sm text-gray-700">{{ $submission->assignment->subject->name }} · {{ $submission->assignment->title }}: <strong>{{ $submission->score ?? '—' }}</strong></p>
                    @empty
                        <p class="mt-3 text-sm text-gray-600">Belum ada nilai yang diterbitkan.</p>
                    @endforelse
                </article>
                <article class="rounded-xl bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Absensi bulan ini</h2>
                    @php($studentAttendance = collect($attendanceSummary)->first())
                    <p class="mt-3 text-sm text-gray-700">Hadir {{ $studentAttendance['hadir'] ?? 0 }} · Izin {{ $studentAttendance['izin'] ?? 0 }} · Sakit {{ $studentAttendance['sakit'] ?? 0 }} · Alpa {{ $studentAttendance['alpa'] ?? 0 }}</p>
                </article>
                <article class="rounded-xl bg-white p-5 shadow-sm lg:col-span-2">
                    <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Tagihan belum lunas</h2>
                    @forelse ($unpaidInvoices as $invoice)
                        <p class="mt-3 text-sm text-gray-700">{{ $invoice->bill_type_name }} · Rp{{ number_format($invoice->amount, 0, ',', '.') }} · Jatuh tempo {{ $invoice->due_date->format('d/m/Y') }}</p>
                    @empty
                        <p class="mt-3 text-sm text-gray-600">Tidak ada tagihan yang perlu dibayar.</p>
                    @endforelse
                </article>
            </section>
        @else
            <section class="space-y-5">
                @forelse ($childrenSummary as $child)
                    <article class="rounded-xl bg-white p-5 shadow-sm">
                        <h1 class="text-xl font-bold text-muhammadiyah-hijau-tua">{{ $child->name }}</h1>
                        <p class="mt-2 text-sm text-gray-700">Kehadiran hadir bulan ini: {{ $child->present_this_month }} hari
                            @if ($child->studentProfile?->schoolClass)
                                · Kelas {{ $child->studentProfile->schoolClass->name }}
                            @endif
                        </p>
                        <p class="mt-1 text-sm text-gray-700">
                            Rekap absensi:
                            @foreach (['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'] as $statusKey => $statusLabel)
                                {{ $statusLabel }} {{ $child->attendance_summary[$statusKey] ?? 0 }}@if (! $loop->last), @endif
                            @endforeach
                        </p>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <h2 class="font-semibold text-gray-900">Nilai terbaru</h2>
                                @forelse ($child->recent_grades as $grade)
                                    <p class="mt-2 text-sm text-gray-700">{{ $grade->assignment->subject->name }} · {{ $grade->assignment->title }}: {{ $grade->score ?? '—' }}</p>
                                @empty
                                    <p class="mt-2 text-sm text-gray-600">Belum ada nilai terbaru.</p>
                                @endforelse
                            </div>
                            <div>
                                <h2 class="font-semibold text-gray-900">Tagihan</h2>
                                @forelse ($child->unpaid_invoices as $invoice)
                                    <p class="mt-2 text-sm text-gray-700">{{ $invoice->bill_type_name }} · Rp{{ number_format($invoice->amount, 0, ',', '.') }} · Jatuh tempo {{ $invoice->due_date->format('d/m/Y') }}</p>
                                @empty
                                    <p class="mt-2 text-sm text-gray-600">Tidak ada tagihan belum lunas.</p>
                                @endforelse
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl bg-white p-5 text-gray-600 shadow-sm">Belum ada anak yang ditautkan ke akun ini.</p>
                @endforelse
            </section>
        @endif

        <section class="rounded-xl bg-white p-5 shadow-sm" aria-labelledby="dashboard-announcements-title">
            <h2 id="dashboard-announcements-title" class="text-lg font-bold text-muhammadiyah-hijau-tua">Pengumuman terbaru</h2>
            <div class="mt-3 space-y-3">
                @forelse ($announcements as $announcement)
                    <article class="rounded-lg border border-gray-100 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muhammadiyah-hijau">{{ $announcement->category }}</p>
                        <h3 class="mt-1 font-bold text-gray-900">{{ $announcement->title }}</h3>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700">{{ \Illuminate\Support\Str::limit($announcement->body, 280) }}</p>
                        <p class="mt-2 text-xs text-gray-500">{{ $announcement->published_at->format('d/m/Y H:i') }}</p>
                    </article>
                @empty
                    <p class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">Belum ada pengumuman untuk peran Anda.</p>
                @endforelse
            </div>
        </section>
    </main>
</x-app-layout>
