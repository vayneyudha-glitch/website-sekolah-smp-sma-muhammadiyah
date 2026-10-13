<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { color: #075e42; font-size: 16px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #b8c9be; padding: 5px; text-align: left; }
        th { background: #e7f2eb; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <table>
        <thead><tr>@foreach ($headings as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>@foreach ($row as $value)<td>{{ is_scalar($value) ? $value : '' }}</td>@endforeach</tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
