<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rapor {{ $report->student->user->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #173a2c; font-size: 11px; }
        .header { text-align: center; border-bottom: 2px solid #b48a20; padding-bottom: 12px; margin-bottom: 18px; }
        h1 { margin: 0 0 4px; font-size: 20px; color: #155b3b; }
        h2 { color: #155b3b; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #b9c9be; padding: 7px; text-align: left; }
        th { background: #e9f2eb; }
        .meta td { border: 0; padding: 3px; }
    </style>
</head>
<body>
    <header class="header">
        <h1>{{ $schoolProfile?->name ?: 'SMP SMA Muhammadiyah Subang' }}</h1>
        <p>{{ $schoolProfile?->tagline }} · {{ $schoolProfile?->address }}</p>
        <p>{{ $schoolProfile?->phone }} · {{ $schoolProfile?->email }}</p>
        <h2>RAPOR HASIL BELAJAR · {{ $report->academicYear->name }}</h2>
    </header>
    <table class="meta">
        <tr><td><strong>Nama murid</strong></td><td>{{ $report->student->user->name }}</td><td><strong>Kelas</strong></td><td>{{ $report->schoolClass->name }}</td></tr>
        <tr><td><strong>NIS</strong></td><td>{{ $report->student->nis }}</td><td><strong>Status</strong></td><td>Terbit</td></tr>
    </table>
    <table>
        <thead><tr><th>Mata pelajaran</th><th>Nilai akhir</th><th>Predikat</th><th>Deskripsi</th></tr></thead>
        <tbody>
            @foreach ($report->items as $item)
                <tr><td>{{ $item->subject->name }}</td><td>{{ number_format((float) $item->final_score, 2, ',', '.') }}</td><td>{{ $item->predicate }}</td><td>{{ $item->description }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <h2>Ringkasan kehadiran</h2>
    <p>Hadir: {{ $attendance['hadir'] ?? 0 }} · Izin: {{ $attendance['izin'] ?? 0 }} · Sakit: {{ $attendance['sakit'] ?? 0 }} · Alpa: {{ $attendance['alpa'] ?? 0 }}</p>
    <h2>Catatan wali kelas</h2>
    <p>{{ $report->homeroom_note ?: '—' }}</p>
</body>
</html>
