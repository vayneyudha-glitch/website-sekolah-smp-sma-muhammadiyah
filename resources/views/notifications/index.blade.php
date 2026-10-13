<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-white">Notifikasi</h2>
    </x-slot>

    <main class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h1 class="text-xl font-bold text-muhammadiyah-hijau-tua">Preferensi email</h1>
            <form method="POST" action="{{ route('notifications.preferences.update') }}" class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                @csrf
                @method('PUT')
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="notification_email_enabled" value="1" @checked(auth()->user()->notification_email_enabled) class="rounded border-gray-300 text-muhammadiyah-hijau focus:ring-muhammadiyah-hijau">
                    <span class="text-sm text-gray-700">Kirim salinan notifikasi melalui email</span>
                </label>
                <button class="min-h-11 rounded-lg bg-muhammadiyah-hijau px-5 font-semibold text-white">Simpan preferensi</button>
            </form>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm">
            <h1 class="text-xl font-bold text-muhammadiyah-hijau-tua">Kotak masuk</h1>
            <div class="mt-4 divide-y divide-gray-100">
                @forelse ($notifications as $notification)
                    <article class="flex flex-col gap-3 py-4 sm:flex-row sm:items-start sm:justify-between {{ $notification->read_at ? 'opacity-75' : '' }}">
                        <div>
                            <h2 class="font-bold text-gray-900">{{ $notification->data['title'] ?? 'Notifikasi sekolah' }}</h2>
                            <p class="mt-1 text-sm text-gray-700">{{ $notification->data['message'] ?? '' }}</p>
                            <p class="mt-2 text-xs text-gray-500">{{ $notification->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @if (! $notification->read_at)
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                @csrf
                                @method('PATCH')
                                <button class="min-h-10 rounded-lg border border-muhammadiyah-hijau px-3 text-sm font-semibold text-muhammadiyah-hijau-tua">Tandai dibaca</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="py-5 text-sm text-gray-600">Belum ada notifikasi.</p>
                @endforelse
            </div>
            {{ $notifications->links() }}
        </section>
    </main>
</x-app-layout>
