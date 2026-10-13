<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Detail Tagihan</h1></x-slot>
    <main class="mx-auto max-w-3xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('error'))<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">{{ session('error') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
            <p class="text-sm font-semibold text-gray-600">{{ $invoice->invoice_number }}</p>
            <h2 class="mt-1 text-2xl font-bold text-muhammadiyah-hijau-tua">{{ $invoice->bill_type_name }}</h2>
            @if (Auth::user()->role === \App\Models\User::ROLE_ORANG_TUA)<p class="mt-2 text-gray-700">Murid: <strong>{{ $invoice->studentProfile->user->name }}</strong></p>@endif
            <dl class="mt-5 divide-y divide-gray-100">
                <div class="flex justify-between gap-4 py-3"><dt class="text-gray-600">Kelas</dt><dd class="text-right font-semibold">{{ $invoice->studentProfile->schoolClass->school_level }} {{ $invoice->studentProfile->schoolClass->grade }} {{ $invoice->studentProfile->schoolClass->name }}</dd></div>
                <div class="flex justify-between gap-4 py-3"><dt class="text-gray-600">Periode</dt><dd class="font-semibold">{{ $invoice->billing_period }}</dd></div>
                <div class="flex justify-between gap-4 py-3"><dt class="text-gray-600">Jatuh tempo</dt><dd class="font-semibold">{{ $invoice->due_date->format('d/m/Y') }}</dd></div>
                <div class="flex justify-between gap-4 py-3"><dt class="text-gray-600">Status</dt><dd class="font-semibold">{{ match ($invoice->status) { 'belum_bayar' => 'Belum bayar', 'menunggu' => 'Menunggu konfirmasi gateway', 'lunas' => 'Lunas', 'kedaluwarsa' => 'Kedaluwarsa', 'dibatalkan' => 'Dibatalkan', default => $invoice->status } }}</dd></div>
                <div class="flex justify-between gap-4 py-3 text-lg"><dt class="font-bold">Total</dt><dd class="font-bold">Rp{{ number_format($invoice->amount, 0, ',', '.') }}</dd></div>
            </dl>
            @if (in_array($invoice->status, [\App\Models\StudentInvoice::STATUS_UNPAID, \App\Models\StudentInvoice::STATUS_EXPIRED], true))
                <div id="bayar" class="mt-5 rounded-xl border border-muhammadiyah-hijau/30 bg-muhammadiyah-hijau-muda p-4">
                    <h3 class="font-bold text-muhammadiyah-hijau-tua">Pembayaran online</h3>
                    @if ($gatewayAvailable)
                        <form method="POST" action="{{ route(Auth::user()->role.'.payments.start', $invoice) }}" class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
                            @csrf
                            <div class="flex-1"><label for="method" class="block text-sm font-semibold">Pilih metode</label><select id="method" name="method" required class="mt-1 min-h-11 w-full rounded-lg border-gray-300"><option value="virtual_account">Virtual account</option><option value="qris">QRIS</option><option value="e_wallet">E-wallet</option></select></div>
                            <button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-bold text-white">Buat pembayaran</button>
                        </form>
                        <p class="mt-3 text-xs text-gray-600">Status hanya diperbarui setelah notifikasi gateway terverifikasi. Jangan mengirim data kartu melalui situs ini.</p>
                    @else
                        <p class="mt-2 text-sm text-gray-700">Gateway belum dikonfigurasi. Hubungi admin sekolah untuk pembayaran tunai atau transfer manual.</p>
                    @endif
                </div>
            @elseif ($invoice->status === \App\Models\StudentInvoice::STATUS_PENDING)
                <p class="mt-5 rounded-lg bg-amber-50 p-4 text-amber-900">Pembayaran sedang menunggu konfirmasi gateway. Status akan berubah otomatis setelah verifikasi server.</p>
            @endif
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-lg font-bold text-muhammadiyah-hijau-tua">Riwayat pembayaran</h2>
            <ul class="mt-3 divide-y divide-gray-100">
                @forelse ($invoice->payments as $payment)
                    <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="font-semibold">{{ ucfirst(str_replace('_', ' ', $payment->method)) }} · {{ ucfirst($payment->status) }}</p><p class="text-sm text-gray-600">{{ $payment->paid_at?->format('d/m/Y H:i') ?? $payment->created_at->format('d/m/Y H:i') }} · Rp{{ number_format($payment->amount, 0, ',', '.') }}</p>@if ($payment->receipt_number)<p class="text-sm text-gray-600">Kuitansi {{ $payment->receipt_number }}</p>@endif</div>
                        @if ($payment->status === \App\Models\Payment::STATUS_PAID)<a href="{{ route(Auth::user()->role.'.payments.receipt', $payment) }}" class="inline-flex min-h-10 items-center rounded-lg border border-muhammadiyah-hijau px-4 font-semibold text-muhammadiyah-hijau-tua">Unduh kuitansi PDF</a>@endif
                    </li>
                @empty<li class="py-4 text-gray-600">Belum ada pembayaran tercatat.</li>@endforelse
            </ul>
        </section>
        <a href="{{ route(Auth::user()->role.'.payments.index') }}" class="inline-flex min-h-10 items-center font-semibold text-muhammadiyah-hijau-tua underline">Kembali ke daftar tagihan</a>
    </main>
</x-app-layout>
