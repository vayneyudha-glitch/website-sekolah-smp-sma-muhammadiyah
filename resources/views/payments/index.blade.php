<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Tagihan Saya</h1></x-slot>
    <main class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if (session('error'))<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">{{ session('error') }}</div>@endif
        <p class="text-gray-700">Murid melihat tagihan miliknya. Orang tua hanya melihat tagihan anak yang tertaut ke akunnya.</p>
        <section class="space-y-3">
            @forelse ($invoices as $invoice)
                <article class="rounded-xl bg-white p-5 shadow-sm sm:flex sm:items-center sm:justify-between sm:gap-5">
                    <div class="min-w-0">
                        @if (Auth::user()->role === \App\Models\User::ROLE_ORANG_TUA)<p class="text-sm font-semibold text-gray-600">{{ $invoice->studentProfile->user->name }}</p>@endif
                        <h2 class="mt-1 text-lg font-bold text-muhammadiyah-hijau-tua">{{ $invoice->bill_type_name }}</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ $invoice->studentProfile->schoolClass->school_level }} {{ $invoice->studentProfile->schoolClass->grade }} {{ $invoice->studentProfile->schoolClass->name }} · Periode {{ $invoice->billing_period }}</p>
                        <p class="mt-1 text-sm text-gray-600">Jatuh tempo {{ $invoice->due_date->format('d/m/Y') }} · Nomor {{ $invoice->invoice_number }}</p>
                        <p class="mt-2 text-lg font-bold">Rp{{ number_format($invoice->amount, 0, ',', '.') }} <span class="text-sm font-semibold">{{ match ($invoice->status) { 'belum_bayar' => '· Belum bayar', 'menunggu' => '· Menunggu konfirmasi', 'lunas' => '· Lunas', 'kedaluwarsa' => '· Kedaluwarsa', 'dibatalkan' => '· Dibatalkan', default => '' } }}</span></p>
                    </div>
                    <div class="mt-4 flex shrink-0 flex-wrap gap-2 sm:mt-0">
                        <a href="{{ route(Auth::user()->role.'.payments.show', $invoice) }}" class="inline-flex min-h-10 items-center rounded-lg border border-muhammadiyah-hijau px-4 font-semibold text-muhammadiyah-hijau-tua">Detail dan riwayat</a>
                        @if (in_array($invoice->status, [\App\Models\StudentInvoice::STATUS_UNPAID, \App\Models\StudentInvoice::STATUS_EXPIRED], true) && $gatewayAvailable)
                            <a href="{{ route(Auth::user()->role.'.payments.show', $invoice) }}#bayar" class="inline-flex min-h-10 items-center rounded-lg bg-muhammadiyah-hijau px-4 font-semibold text-white">Bayar online</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-xl bg-white p-8 text-center text-gray-600">Belum ada tagihan yang tersedia.</div>
            @endforelse
        </section>
        @if ($invoices->hasPages())<div>{{ $invoices->links() }}</div>@endif
    </main>
</x-app-layout>
