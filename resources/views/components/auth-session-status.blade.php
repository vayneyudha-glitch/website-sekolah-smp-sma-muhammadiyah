@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-muhammadiyah-hijau']) }}>
        {{ $status }}
    </div>
@endif
