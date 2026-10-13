<x-layouts.public title="Kontak" description="Alamat dan informasi kontak sekolah.">
    @php
        $whatsapp = $schoolProfile->whatsapp !== '[Isi dari panel admin]' && filled($schoolProfile->whatsapp)
            ? $schoolProfile->whatsapp
            : config('school.whatsapp');
        $mapUrl = filled($schoolProfile->map_link) ? parse_url($schoolProfile->map_link) : false;
        $mapEmbedUrl = is_array($mapUrl)
            && ($mapUrl['scheme'] ?? null) === 'https'
            && in_array($mapUrl['host'] ?? '', ['www.google.com', 'maps.google.com'], true)
            && str_starts_with($mapUrl['path'] ?? '', '/maps/embed')
            ? $schoolProfile->map_link
            : null;
        if (! $mapEmbedUrl
            && is_numeric($schoolProfile->latitude)
            && is_numeric($schoolProfile->longitude)
            && (float) $schoolProfile->latitude >= -90 && (float) $schoolProfile->latitude <= 90
            && (float) $schoolProfile->longitude >= -180 && (float) $schoolProfile->longitude <= 180) {
            $mapEmbedUrl = 'https://maps.google.com/maps?q='.rawurlencode($schoolProfile->latitude.','.$schoolProfile->longitude).'&output=embed';
        }
    @endphp
    <main class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <section class="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-3xl font-bold text-muhammadiyah-hijau-tua">Kontak Sekolah</h1>
            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                @foreach (['Alamat' => $schoolProfile->address ?? '[Isi dari panel admin]', 'Telepon' => $schoolProfile->phone ?? '[Isi dari panel admin]', 'Email' => $schoolProfile->email ?? '[Isi dari panel admin]', 'Jam layanan' => $schoolProfile->service_hours ?? '[Isi dari panel admin]'] as $label => $value)
                    <div class="rounded-lg bg-muhammadiyah-hijau-muda p-4">
                        <dt class="text-sm font-semibold text-gray-600">{{ $label }}</dt>
                        <dd class="mt-1 break-words font-medium text-gray-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
            <div class="mt-6 flex flex-wrap gap-3">
                @if ($whatsapp !== '[Isi dari panel admin]' && filled($whatsapp))
                    <a href="https://wa.me/{{ preg_replace('/\D+/', '', $whatsapp) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 items-center justify-center rounded-lg bg-green-700 px-5 font-bold text-white hover:bg-green-800">Hubungi melalui WhatsApp</a>
                @endif
            </div>
        </section>
        @if ($schoolProfile->social_links)
            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-muhammadiyah-hijau-tua">Media Sosial</h2>
                <ul class="mt-3 flex flex-wrap gap-3">
                    @foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'tiktok' => 'TikTok'] as $key => $label)
                        @php
                            $socialValue = $schoolProfile->social_links[$key] ?? '[Isi dari panel admin]';
                            $socialUrl = filter_var($socialValue, FILTER_VALIDATE_URL) ? parse_url($socialValue) : false;
                            $socialHost = ['instagram.com', 'www.instagram.com', 'facebook.com', 'www.facebook.com', 'youtube.com', 'www.youtube.com', 'youtu.be', 'tiktok.com', 'www.tiktok.com'];
                        @endphp
                        @if ($socialValue !== '[Isi dari panel admin]' && filled($socialValue))
                            <li>
                                @if (is_array($socialUrl) && ($socialUrl['scheme'] ?? null) === 'https' && in_array($socialUrl['host'] ?? '', $socialHost, true))
                                    <a href="{{ $socialValue }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center rounded-lg border border-muhammadiyah-hijau px-4 font-semibold text-muhammadiyah-hijau-tua underline">{{ $label }}</a>
                                @else
                                    <span class="inline-flex min-h-11 items-center rounded-lg bg-muhammadiyah-hijau-muda px-4 font-semibold text-muhammadiyah-hijau-tua">{{ $label }}: {{ $socialValue }}</span>
                                @endif
                            </li>
                        @endif
                    @endforeach
                </ul>
            </section>
        @endif
        <section class="overflow-hidden rounded-2xl bg-white p-4 shadow-sm sm:p-6">
            <h2 class="mb-4 text-xl font-bold text-muhammadiyah-hijau-tua">Peta lokasi</h2>
            @if ($mapEmbedUrl)
                <iframe src="{{ $mapEmbedUrl }}" title="Peta lokasi sekolah" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="h-72 w-full rounded-lg border-0 sm:h-96"></iframe>
            @else
                <p class="rounded-lg bg-muhammadiyah-hijau-muda p-5 text-gray-700">Peta lokasi akan ditampilkan setelah tautan peta atau koordinat diisi oleh admin.</p>
            @endif
        </section>
    </main>
</x-layouts.public>
