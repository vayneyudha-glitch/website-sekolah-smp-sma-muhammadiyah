<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-muhammadiyah-hijau border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-muhammadiyah-hijau-tua focus:bg-muhammadiyah-hijau-tua active:bg-muhammadiyah-hijau-tua focus:outline-none focus:ring-2 focus:ring-muhammadiyah-kuning focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
