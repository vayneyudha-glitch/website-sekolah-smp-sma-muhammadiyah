<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-white">Pengaturan Profil Sekolah</h1></x-slot>
    <main class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-green-900">{{ session('status') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800">Periksa kembali isian yang ditandai.</div>@endif
        <form method="POST" action="{{ route('admin.school-content.profile.update') }}" class="space-y-6 rounded-xl bg-white p-5 shadow-sm sm:p-8">
            @csrf
            @method('PUT')
            @php($fields = [
                'name' => ['Nama sekolah', 'text'],
                'tagline' => ['Tagline', 'text'],
                'address' => ['Alamat', 'textarea'],
                'phone' => ['Telepon', 'text'],
                'whatsapp' => ['Nomor WhatsApp', 'text'],
                'email' => ['Email', 'text'],
                'latitude' => ['Latitude', 'text'],
                'longitude' => ['Longitude', 'text'],
                'map_link' => ['Link sematan Google Maps (opsional)', 'text'],
                'service_hours' => ['Jam layanan', 'text'],
                'welcome_message' => ['Isi sambutan', 'textarea'],
                'vision' => ['Visi', 'textarea'],
                'mission' => ['Misi', 'textarea'],
                'history' => ['Sejarah', 'textarea'],
                'smp_description' => ['Informasi jenjang SMP', 'textarea'],
                'sma_description' => ['Informasi jenjang SMA', 'textarea'],
            ])
            @foreach ($fields as $name => [$label, $type])
                <div>
                    <label for="{{ $name }}" class="mb-1 block text-sm font-semibold text-gray-800">{{ $label }}</label>
                    @if ($type === 'textarea')
                        <textarea id="{{ $name }}" name="{{ $name }}" rows="4" required class="w-full rounded-lg border-gray-300 focus:border-muhammadiyah-hijau focus:ring-muhammadiyah-hijau">{{ old($name, $setting->{$name}) }}</textarea>
                    @else
                        <input id="{{ $name }}" name="{{ $name }}" type="text" value="{{ old($name, $setting->{$name}) }}" required class="min-h-12 w-full rounded-lg border-gray-300 focus:border-muhammadiyah-hijau focus:ring-muhammadiyah-hijau">
                    @endif
                    @error($name)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <fieldset class="grid gap-4 border-t border-gray-100 pt-5 sm:grid-cols-2">
                <legend class="px-1 font-bold text-muhammadiyah-hijau-tua">Akun media sosial</legend>
                @foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'tiktok' => 'TikTok'] as $key => $label)
                    <div>
                        <label for="social-{{ $key }}" class="mb-1 block text-sm font-semibold text-gray-800">{{ $label }}</label>
                        <input id="social-{{ $key }}" name="social_links[{{ $key }}]" type="text" value="{{ old('social_links.'.$key, $setting->social_links[$key] ?? '[Isi dari panel admin]') }}" required class="min-h-12 w-full rounded-lg border-gray-300">
                        @error('social_links.'.$key)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </fieldset>
            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-between">
                <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-gray-300 px-5 font-semibold text-gray-700">Kembali</a>
                <button class="inline-flex min-h-12 items-center justify-center rounded-lg bg-muhammadiyah-hijau px-6 font-bold text-white hover:bg-muhammadiyah-hijau-tua">Simpan profil</button>
            </div>
        </form>
    </main>
</x-app-layout>
