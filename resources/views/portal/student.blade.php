<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-white">Data Sekolah Saya</h1>
    </x-slot>

    <main class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7">
            <p class="text-sm font-semibold text-gray-600">Informasi murid</p>
            <h2 class="mt-1 text-2xl font-bold text-muhammadiyah-hijau-tua">{{ $student->user->name }}</h2>
            <dl class="mt-5 divide-y divide-gray-100">
                <div class="flex flex-col gap-1 py-3 sm:flex-row sm:justify-between">
                    <dt class="font-medium text-gray-600">NIS</dt><dd class="font-semibold text-gray-900">{{ $student->nis }}</dd>
                </div>
                <div class="flex flex-col gap-1 py-3 sm:flex-row sm:justify-between">
                    <dt class="font-medium text-gray-600">NISN</dt><dd class="font-semibold text-gray-900">{{ $student->nisn ?: 'Belum diisi' }}</dd>
                </div>
                <div class="flex flex-col gap-1 py-3 sm:flex-row sm:justify-between">
                    <dt class="font-medium text-gray-600">Status</dt><dd class="font-semibold text-gray-900">{{ ucfirst($student->status) }}</dd>
                </div>
                <div class="flex flex-col gap-1 py-3 sm:flex-row sm:justify-between">
                    <dt class="font-medium text-gray-600">Kelas</dt><dd class="font-semibold text-gray-900">{{ $student->schoolClass->school_level }} {{ $student->schoolClass->grade }} {{ $student->schoolClass->name }}</dd>
                </div>
                <div class="flex flex-col gap-1 py-3 sm:flex-row sm:justify-between">
                    <dt class="font-medium text-gray-600">Tahun ajaran</dt><dd class="font-semibold text-gray-900">{{ $student->schoolClass->academicYear->name }}</dd>
                </div>
            </dl>
        </section>
    </main>
</x-app-layout>
