<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-white">{{ $record ? 'Ubah' : 'Tambah' }} {{ strtolower($title) }}</h1>
    </x-slot>

    <main class="mx-auto max-w-3xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
            @if (in_array($resource, ['accounts', 'students', 'teachers', 'parents'], true) && ! $record)
                <p class="mb-5 rounded-lg bg-green-50 p-4 text-sm text-green-900">Akun akan dibuat dengan kata sandi sementara. Simpan kata sandi yang ditampilkan setelah data disimpan.</p>
            @endif
            <form method="POST" enctype="multipart/form-data" action="{{ $record ? route('admin.data.update', ['resource' => $resource, 'record' => $record->getKey()]) : route('admin.data.store', ['resource' => $resource]) }}" class="space-y-5">
                @csrf
                @if ($record)
                    @method('PUT')
                @endif

                @foreach ($fields as $field)
                    @php
                        $name = $field['name'];
                        $inputName = ($field['multiple'] ?? false) ? $name.'[]' : $name;
                        $value = old($name, $values[$name] ?? ($field['default'] ?? null));
                    @endphp
                    <div>
                        <label for="{{ $name }}" class="mb-1 block text-sm font-semibold text-gray-800">{{ $field['label'] }}</label>
                        @if ($field['type'] === 'select')
                            <select id="{{ $name }}" name="{{ $inputName }}" @if ($field['multiple'] ?? false) multiple size="5" @elseif (! ($field['optional'] ?? false)) required @endif class="min-h-12 w-full rounded-lg border-gray-300 focus:border-muhammadiyah-hijau focus:ring-muhammadiyah-hijau">
                                @if (! ($field['multiple'] ?? false))
                                    <option value="">{{ ($field['optional'] ?? false) ? 'Publik / tidak dipilih' : 'Pilih '.strtolower($field['label']) }}</option>
                                @endif
                                @foreach (($field['choices'] ?? $options[$field['options']]) as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}" @selected(($field['multiple'] ?? false) ? in_array((string) $optionValue, array_map('strval', (array) $value), true) : (string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                            @if ($field['multiple'] ?? false)
                                <p class="mt-1 text-sm text-gray-600">Pilih satu atau beberapa data. Gunakan Ctrl atau tekan lama di ponsel.</p>
                            @endif
                        @elseif ($field['type'] === 'textarea')
                            <textarea id="{{ $name }}" name="{{ $name }}" rows="5" class="w-full rounded-lg border-gray-300 focus:border-muhammadiyah-hijau focus:ring-muhammadiyah-hijau" @if ($name === 'body') required @endif>{{ $value }}</textarea>
                        @elseif ($field['type'] === 'checkbox')
                            <label class="flex min-h-12 items-center gap-3 rounded-lg border border-gray-200 px-4">
                                <input id="{{ $name }}" type="checkbox" name="{{ $name }}" value="1" @checked((bool) old($name, $values[$name] ?? ($field['default'] ?? false))) class="h-5 w-5 rounded border-gray-300 text-muhammadiyah-hijau focus:ring-muhammadiyah-hijau">
                                <span class="text-sm text-gray-700">Ya</span>
                            </label>
                        @elseif ($field['type'] === 'file')
                            @if ($resource === 'teachers' && $record?->photo_path)
                                <img src="{{ asset('storage/'.$record->photo_path) }}" alt="Foto {{ $record->user->name }}" class="mb-3 h-24 w-24 rounded-full object-cover">
                            @elseif ($resource === 'announcements' && $record?->image_path)
                                <img src="{{ asset('storage/'.$record->image_path) }}" alt="Gambar berita saat ini" class="mb-3 max-h-48 rounded-lg object-cover">
                            @endif
                            <input id="{{ $name }}" type="file" name="{{ $name }}" accept="image/*" @if (! ($field['optional'] ?? false)) required @endif class="block min-h-12 w-full rounded-lg border border-gray-300 p-2 text-sm text-gray-700">
                        @else
                            <input id="{{ $name }}" type="{{ $field['type'] }}" name="{{ $name }}" value="{{ $value }}" @if (! ($field['optional'] ?? false)) required @endif class="min-h-12 w-full rounded-lg border-gray-300 focus:border-muhammadiyah-hijau focus:ring-muhammadiyah-hijau">
                        @endif
                        @error($name)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                @if ($resource === 'announcements')
                    <p class="text-sm text-gray-600">Tanggal terbit yang dikosongkan akan menggunakan waktu saat ini. Pengumuman dengan penerima tertentu hanya terlihat oleh peran tersebut setelah ditayangkan.</p>
                @endif
                @if ($errors->any())
                    <div role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-800">Periksa kembali isian yang ditandai dan coba simpan lagi.</div>
                @endif
                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-between">
                    <a href="{{ route('admin.data.index', ['resource' => $resource]) }}" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-gray-300 px-5 font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
                    <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-lg bg-muhammadiyah-hijau px-6 font-bold text-white hover:bg-muhammadiyah-hijau-tua">{{ $record ? 'Simpan perubahan' : 'Simpan data' }}</button>
                </div>
            </form>
        </section>
    </main>
</x-app-layout>
