<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-xl font-bold text-white">Pembayaran dan Tunggakan</h1>
            <a href="{{ route('admin.payments.types') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-muhammadiyah-kuning px-4 font-bold text-muhammadiyah-hijau-tua">Kelola jenis dan terbitkan tagihan</a>
        </div>
    </x-slot>
    <main class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if (session('error'))<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">{{ session('error') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section class="grid gap-4 md:grid-cols-2">
            <article class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="font-bold text-muhammadiyah-hijau-tua">Rekap pembayaran lunas per bulan</h2>
                <ul class="mt-3 divide-y divide-gray-100">
                    @forelse ($monthlySummary as $period => $amount)<li class="flex justify-between py-2"><span>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $period)->format('m/Y') }}</span><strong>Rp{{ number_format($amount, 0, ',', '.') }}</strong></li>
                    @empty<li class="py-2 text-gray-600">Belum ada pembayaran lunas dalam 12 bulan terakhir.</li>@endforelse
                </ul>
            </article>
            <article class="rounded-xl bg-white p-5 shadow-sm">
                <h2 class="font-bold text-muhammadiyah-hijau-tua">Rekap kelas · {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('m/Y') }}</h2>
                <ul class="mt-3 divide-y divide-gray-100">
                    @forelse ($classSummary as $className => $amount)<li class="flex justify-between py-2"><span>{{ $className }}</span><strong>Rp{{ number_format($amount, 0, ',', '.') }}</strong></li>
                    @empty<li class="py-2 text-gray-600">Belum ada pembayaran lunas pada bulan ini.</li>@endforelse
                </ul>
            </article>
        </section>

        <form method="GET" action="{{ route('admin.payments.index') }}" class="grid gap-3 rounded-xl bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
            <div><label for="status" class="block text-sm font-semibold">Status pembayaran</label><select id="status" name="status" class="mt-1 min-h-10 w-full rounded-lg border-gray-300"><option value="">Semua</option>@foreach ($paymentStatuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
            <div><label for="school_class_id" class="block text-sm font-semibold">Kelas</label><select id="school_class_id" name="school_class_id" class="mt-1 min-h-10 w-full rounded-lg border-gray-300"><option value="">Semua kelas</option>@foreach ($classes as $class)<option value="{{ $class->id }}" @selected(($filters['school_class_id'] ?? '') == $class->id)>{{ $class->school_level }} {{ $class->grade }} {{ $class->name }}</option>@endforeach</select></div>
            <div><label for="school_level" class="block text-sm font-semibold">Jenjang</label><select id="school_level" name="school_level" class="mt-1 min-h-10 w-full rounded-lg border-gray-300"><option value="">Semua jenjang</option><option value="SMP" @selected(($filters['school_level'] ?? '') === 'SMP')>SMP</option><option value="SMA" @selected(($filters['school_level'] ?? '') === 'SMA')>SMA</option></select></div>
            <div><label for="month" class="block text-sm font-semibold">Bulan pencatatan</label><input id="month" name="month" type="month" value="{{ $month }}" class="mt-1 min-h-10 w-full rounded-lg border-gray-300"></div>
            <div class="flex items-end gap-2"><button class="min-h-10 rounded-lg bg-muhammadiyah-hijau px-4 font-semibold text-white">Filter</button><a href="{{ route('admin.payments.export', request()->query()) }}" class="inline-flex min-h-10 items-center rounded-lg border border-muhammadiyah-hijau px-3 text-sm font-semibold text-muhammadiyah-hijau-tua">Ekspor Excel</a></div>
        </form>

        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <h2 class="px-4 pt-5 text-lg font-bold text-muhammadiyah-hijau-tua sm:px-6">Daftar pembayaran</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3">Murid / Kelas</th><th class="px-4 py-3">Tagihan</th><th class="px-4 py-3">Nominal</th><th class="px-4 py-3">Metode / Status</th><th class="px-4 py-3">Waktu bayar</th><th class="px-4 py-3">Kuitansi / Bukti</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="px-4 py-3">{{ $payment->invoice->studentProfile->user->name }}<br><span class="text-gray-500">{{ $payment->invoice->studentProfile->schoolClass->school_level }} {{ $payment->invoice->studentProfile->schoolClass->grade }} {{ $payment->invoice->studentProfile->schoolClass->name }}</span></td>
                                <td class="px-4 py-3">{{ $payment->invoice->bill_type_name }}<br><span class="text-gray-500">{{ $payment->invoice->invoice_number }} · {{ $payment->invoice->billing_period }}</span></td>
                                <td class="px-4 py-3">Rp{{ number_format($payment->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}<br><span>{{ ucfirst(str_replace('_', ' ', $payment->status)) }}</span></td>
                                <td class="px-4 py-3">{{ $payment->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3">@if ($payment->receipt_number){{ $payment->receipt_number }}@endif @if ($payment->proof_path)<a class="block font-semibold text-muhammadiyah-hijau-tua underline" href="{{ route('admin.payments.proof', $payment) }}">Unduh bukti</a>@endif</td>
                            </tr>
                        @empty<tr><td colspan="6" class="px-4 py-8 text-center text-gray-600">Belum ada data pembayaran untuk filter ini.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-4 py-3">{{ $payments->links() }}</div>
        </section>

        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <h2 class="px-4 pt-5 text-lg font-bold text-muhammadiyah-hijau-tua sm:px-6">Daftar tagihan belum lunas · {{ $openInvoices->count() }} teratas</h2>
            <div class="mt-3 divide-y divide-gray-100">
                @forelse ($openInvoices as $invoice)
                    <article class="grid gap-4 p-4 sm:grid-cols-[1fr_auto] sm:items-start sm:px-6">
                        <div><h3 class="font-semibold">{{ $invoice->studentProfile->user->name }} · {{ $invoice->studentProfile->schoolClass->school_level }} {{ $invoice->studentProfile->schoolClass->grade }} {{ $invoice->studentProfile->schoolClass->name }}</h3><p class="mt-1 text-sm text-gray-600">{{ $invoice->bill_type_name }} · {{ $invoice->billing_period }} · Jatuh tempo {{ $invoice->due_date->format('d/m/Y') }} · Rp{{ number_format($invoice->amount, 0, ',', '.') }}</p><p class="mt-1 text-sm font-semibold text-amber-800">{{ $invoice->due_date->lt(today()) ? 'Terlambat · ' : '' }}{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</p></div>
                        <div class="grid gap-2 sm:min-w-72">
                            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.payments.manual', $invoice) }}" class="grid gap-2 rounded-lg bg-gray-50 p-3">
                                @csrf
                                <label class="text-sm font-semibold">Catat pembayaran manual</label>
                                <select name="method" required class="min-h-10 rounded-lg border-gray-300"><option value="tunai">Tunai</option><option value="transfer">Transfer</option></select>
                                <input name="paid_at" type="datetime-local" class="min-h-10 rounded-lg border-gray-300" aria-label="Waktu pembayaran">
                                <label class="text-sm">Bukti wajib (PDF/JPG/PNG, maks. 5 MB)<input name="proof" type="file" accept=".pdf,.jpg,.jpeg,.png" required class="mt-1 block w-full text-sm"></label>
                                <button class="min-h-10 rounded-lg bg-muhammadiyah-hijau px-3 font-semibold text-white">Simpan pembayaran lunas</button>
                            </form>
                            <form method="POST" action="{{ route('admin.payments.cancel', $invoice) }}" class="grid gap-2 rounded-lg border border-red-100 p-3">
                                @csrf
                                <label class="text-sm font-semibold" for="cancel_reason_{{ $invoice->id }}">Batalkan tagihan</label>
                                <input id="cancel_reason_{{ $invoice->id }}" name="reason" required minlength="5" maxlength="500" class="min-h-10 rounded-lg border-gray-300" placeholder="Alasan pembatalan">
                                <button class="min-h-10 rounded-lg border border-red-300 px-3 font-semibold text-red-700">Batalkan</button>
                            </form>
                        </div>
                    </article>
                @empty<div class="p-6 text-center text-gray-600">Tidak ada tagihan yang belum lunas.</div>@endforelse
            </div>
        </section>
    </main>
</x-app-layout>
