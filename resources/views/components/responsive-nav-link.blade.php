@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-muhammadiyah-kuning text-start text-base font-medium text-muhammadiyah-hijau-tua bg-muhammadiyah-hijau-muda focus:outline-none focus:text-muhammadiyah-hijau-tua focus:bg-muhammadiyah-hijau-muda focus:border-muhammadiyah-kuning transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-white/85 hover:text-white hover:bg-muhammadiyah-hijau hover:border-muhammadiyah-kuning focus:outline-none focus:text-white focus:bg-muhammadiyah-hijau focus:border-muhammadiyah-kuning transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
