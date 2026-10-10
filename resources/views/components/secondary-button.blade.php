<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white border border-muhammadiyah-hijau/30 rounded-md font-semibold text-xs text-muhammadiyah-hijau-tua uppercase tracking-widest shadow-sm hover:bg-muhammadiyah-hijau-muda focus:outline-none focus:ring-2 focus:ring-muhammadiyah-hijau focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
