<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kuitansi {{ $payment->receipt_number }}</title>
    <style>
        body { color: #18372e; font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 36px; }
        h1 { color: #14532d; font-size: 22px; margin-bottom: 4px; }
        .subtitle { color: #64748b; margin-top: 0; }
        table { border-collapse: collapse; margin-top: 24px; width: 100%; }
        th, td { border-bottom: 1px solid #d1d5db; padding: 11px 8px; text-align: left; }
        th { width: 35%; }
        .total { color: #14532d; font-size: 18px; font-weight: bold; }
        .paid { background: #dcfce7; color: #166534; display: inline-block; font-weight: bold; margin-top: 20px; padding: 8px 12px; }
    </style>
</head>
<body>
    <h1>Kuitansi Pembayaran</h1>
    <p class="subtitle">SMP SMA Muhammadiyah Subang</p>
    <table>
        <tr><th>Nomor kuitansi</th><td>{{ $payment->receipt_number }}</td></tr>
        <tr><th>Nama murid</th><td>{{ $payment->invoice->studentProfile->user->name }}</td></tr>
        <tr><th>Kelas</th><td>{{ $payment->invoice->studentProfile->schoolClass->school_level }} {{ $payment->invoice->studentProfile->schoolClass->grade }} {{ $payment->invoice->studentProfile->schoolClass->name }}</td></tr>
        <tr><th>Jenis tagihan</th><td>{{ $payment->invoice->bill_type_name }}</td></tr>
        <tr><th>Periode</th><td>{{ $payment->invoice->billing_period }}</td></tr>
        <tr><th>Nomor tagihan</th><td>{{ $payment->invoice->invoice_number }}</td></tr>
        <tr><th>Metode pembayaran</th><td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td></tr>
        <tr><th>Tanggal pembayaran</th><td>{{ $payment->paid_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><th>Total dibayar</th><td class="total">Rp{{ number_format($payment->amount, 0, ',', '.') }}</td></tr>
    </table>
    <p class="paid">LUNAS</p>
</body>
</html>
