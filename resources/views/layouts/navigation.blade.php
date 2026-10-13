@php
    $user = Auth::user();
    $isAdmin = $user->role === \App\Models\User::ROLE_ADMIN;
    $isTeacher = $user->role === \App\Models\User::ROLE_GURU;
    $isStudent = $user->role === \App\Models\User::ROLE_SISWA;
    $isParent = $user->role === \App\Models\User::ROLE_ORANG_TUA;
    $isApplicant = $user->role === \App\Models\User::ROLE_PENDAFTAR;
    $hasAcceptedPpdb = $isStudent && $user->ppdbApplication()->where('status', 'accepted')->exists();
    $unreadNotificationCount = $user->unreadNotifications()->count();
@endphp

<nav x-data="{ open: false }" class="bg-muhammadiyah-hijau-tua border-b border-muhammadiyah-hijau">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-2">
            <div class="flex min-w-0 items-center">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route($user->dashboardRouteName()) }}" class="flex items-center gap-3">
                        <x-application-logo />
                        <span class="hidden text-sm font-bold text-white lg:block">SMP SMA Muhammadiyah Subang</span>
                    </a>
                </div>

                <div class="ms-4 hidden min-w-0 items-center gap-1 xl:flex">
                    <x-nav-link :href="route($user->dashboardRouteName())" :active="request()->routeIs($user->dashboardRouteName())">
                        Beranda
                    </x-nav-link>

                    @if ($isAdmin)
                        <x-dropdown align="left" width="w-56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-1 whitespace-nowrap border-b-2 px-2 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ request()->routeIs('admin.data.*') && in_array(request()->route('resource'), ['academic-years', 'classes', 'subjects', 'timetables'], true) ? 'border-muhammadiyah-kuning text-white' : 'border-transparent text-white/80 hover:border-muhammadiyah-kuning hover:text-white' }}">
                                    Akademik
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('admin.data.index', ['resource' => 'academic-years'])">Tahun Ajaran</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.data.index', ['resource' => 'classes'])">Kelas</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.data.index', ['resource' => 'subjects'])">Mata Pelajaran</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.data.index', ['resource' => 'timetables'])">Jadwal</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>

                        <x-dropdown align="left" width="w-56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-1 whitespace-nowrap border-b-2 px-2 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ request()->routeIs('admin.data.*') && in_array(request()->route('resource'), ['accounts', 'students', 'teachers', 'parents', 'parent-links'], true) ? 'border-muhammadiyah-kuning text-white' : 'border-transparent text-white/80 hover:border-muhammadiyah-kuning hover:text-white' }}">
                                    Pengguna
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('admin.data.index', ['resource' => 'accounts'])">Akun</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.data.index', ['resource' => 'students'])">Murid</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.data.index', ['resource' => 'teachers'])">Guru</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.data.index', ['resource' => 'parents'])">Orang Tua</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.data.index', ['resource' => 'parent-links'])">Hubungan Orang Tua dan Anak</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>

                        <x-dropdown align="left" width="w-56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-1 whitespace-nowrap border-b-2 px-2 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ request()->routeIs('admin.ppdb.*', 'ppdb.information') ? 'border-muhammadiyah-kuning text-white' : 'border-transparent text-white/80 hover:border-muhammadiyah-kuning hover:text-white' }}">
                                    PPDB
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('admin.ppdb.index')">Kelola Pendaftaran</x-dropdown-link>
                                <x-dropdown-link :href="route('ppdb.information')">Informasi PPDB</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>

                        <x-nav-link :href="route('admin.attendance.settings')" :active="request()->routeIs('admin.attendance.*')">Absensi</x-nav-link>
                        <x-nav-link :href="route('admin.payments.index')" :active="request()->routeIs('admin.payments.*')">Keuangan</x-nav-link>

                        <x-dropdown align="left" width="w-56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-1 whitespace-nowrap border-b-2 px-2 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ request()->routeIs('admin.data.*', 'public.announcements', 'public.announcement', 'public.teachers') && (request()->route('resource') === 'announcements' || request()->routeIs('public.*')) ? 'border-muhammadiyah-kuning text-white' : 'border-transparent text-white/80 hover:border-muhammadiyah-kuning hover:text-white' }}">
                                    Konten
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('admin.data.index', ['resource' => 'announcements'])">Pengumuman dan Berita</x-dropdown-link>
                                <x-dropdown-link :href="route('public.announcements')">Berita Sekolah</x-dropdown-link>
                                <x-dropdown-link :href="route('public.teachers')">Daftar Guru</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.school-content.profile.edit')">Profil Sekolah</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.school-content.facilities')">Fasilitas</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.school-content.gallery')">Galeri</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>

                        <x-dropdown align="left" width="w-56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-1 whitespace-nowrap border-b-2 px-2 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ request()->routeIs('admin.audit.*', 'admin.exports.*') ? 'border-muhammadiyah-kuning text-white' : 'border-transparent text-white/80 hover:border-muhammadiyah-kuning hover:text-white' }}">
                                    Administrasi
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('admin.audit.index')">Aktivitas</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.exports.index')">Ekspor</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @elseif ($isApplicant)
                        <x-dropdown align="left" width="w-56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-1 whitespace-nowrap border-b-2 px-2 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ request()->routeIs('pendaftar.*', 'ppdb.information') ? 'border-muhammadiyah-kuning text-white' : 'border-transparent text-white/80 hover:border-muhammadiyah-kuning hover:text-white' }}">
                                    PPDB
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('pendaftar.dashboard')">Pendaftaran Saya</x-dropdown-link>
                                <x-dropdown-link :href="route('ppdb.information')">Informasi PPDB</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @else
                        <x-dropdown align="left" width="w-56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-1 whitespace-nowrap border-b-2 px-2 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ request()->routeIs('*.timetables.*', 'guru.assignments.*', 'guru.classes.*', 'siswa.assignments.*', 'siswa.student-record.*', 'orang_tua.children.*') ? 'border-muhammadiyah-kuning text-white' : 'border-transparent text-white/80 hover:border-muhammadiyah-kuning hover:text-white' }}">
                                    Akademik
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route($user->role.'.timetables.index')">Jadwal</x-dropdown-link>
                                @if ($isTeacher)
                                    <x-dropdown-link :href="route('guru.assignments.index')">Tugas</x-dropdown-link>
                                    <x-dropdown-link :href="route('guru.classes.index')">Kelas Saya</x-dropdown-link>
                                @elseif ($isStudent)
                                    <x-dropdown-link :href="route('siswa.assignments.index')">Tugas</x-dropdown-link>
                                    <x-dropdown-link :href="route('siswa.student-record.index')">Data Saya</x-dropdown-link>
                                @elseif ($isParent)
                                    <x-dropdown-link :href="route('orang_tua.children.index')">Data Anak</x-dropdown-link>
                                @endif
                            </x-slot>
                        </x-dropdown>

                        @if ($isTeacher)
                            <x-nav-link :href="route('guru.attendance.index')" :active="request()->routeIs('guru.attendance.*')">Absensi</x-nav-link>
                            <x-nav-link :href="route('guru.exports.index')" :active="request()->routeIs('guru.exports.*')">Ekspor</x-nav-link>
                        @elseif ($isStudent)
                            <x-nav-link :href="route('siswa.attendance.index')" :active="request()->routeIs('siswa.attendance.*')">Absensi</x-nav-link>
                            <x-nav-link :href="route('siswa.payments.index')" :active="request()->routeIs('siswa.payments.*')">Keuangan</x-nav-link>
                        @else
                            <x-nav-link :href="route('orang_tua.payments.index')" :active="request()->routeIs('orang_tua.payments.*')">Keuangan</x-nav-link>
                        @endif

                        <x-dropdown align="left" width="w-56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-1 whitespace-nowrap border-b-2 px-2 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ request()->routeIs('public.teachers', 'public.announcements', 'public.announcement') ? 'border-muhammadiyah-kuning text-white' : 'border-transparent text-white/80 hover:border-muhammadiyah-kuning hover:text-white' }}">
                                    Konten
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('public.teachers')">Daftar Guru</x-dropdown-link>
                                <x-dropdown-link :href="route('public.announcements')">Berita</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>

                        <x-dropdown align="left" width="w-56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-1 whitespace-nowrap border-b-2 px-2 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ request()->routeIs('ppdb.information') || ($hasAcceptedPpdb && request()->routeIs('pendaftar.*')) ? 'border-muhammadiyah-kuning text-white' : 'border-transparent text-white/80 hover:border-muhammadiyah-kuning hover:text-white' }}">
                                    PPDB
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('ppdb.information')">Informasi PPDB</x-dropdown-link>
                                @if ($hasAcceptedPpdb)
                                    <x-dropdown-link :href="route('pendaftar.dashboard')">Status PPDB</x-dropdown-link>
                                @endif
                            </x-slot>
                        </x-dropdown>
                    @endif
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-1">
                <x-dropdown align="right" width="w-56">
                    <x-slot name="trigger">
                        <button class="inline-flex max-w-[9rem] items-center gap-1 rounded-md px-2 py-2 text-sm font-medium leading-4 text-white transition duration-150 ease-in-out hover:text-muhammadiyah-kuning focus:outline-none focus:ring-2 focus:ring-muhammadiyah-kuning sm:px-3">
                            <span class="min-w-0 max-w-[6.5rem] truncate" title="{{ $user->name }}">{{ $user->name }}</span>
                            <svg class="h-4 w-4 shrink-0 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="border-b border-muhammadiyah-hijau-muda px-4 py-2">
                            <div class="font-semibold text-muhammadiyah-hijau-tua">{{ $user->name }}</div>
                            <div class="truncate text-xs text-muhammadiyah-hijau-tua/70">{{ $user->email }}</div>
                        </div>
                        <x-dropdown-link :href="route('notifications.index')">Notifikasi ({{ $unreadNotificationCount }})</x-dropdown-link>
                        <x-dropdown-link :href="route('profile.edit')">Profil</x-dropdown-link>
                        <x-dropdown-link :href="route('legal.privacy')">Kebijakan Privasi</x-dropdown-link>
                        <x-dropdown-link :href="route('legal.terms')">Syarat Penggunaan</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Keluar</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>

                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-white transition duration-150 ease-in-out hover:bg-muhammadiyah-hijau hover:text-muhammadiyah-kuning focus:outline-none focus:ring-2 focus:ring-muhammadiyah-kuning xl:hidden" :aria-label="open ? 'Tutup menu navigasi' : 'Buka menu navigasi'" :aria-expanded="open.toString()">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path :class="{ 'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{ 'block': open, 'hidden': ! open }" class="hidden border-t border-muhammadiyah-hijau xl:hidden">
        <div class="max-h-[calc(100vh-4rem)] overflow-y-auto px-4 pb-4 pt-2">
            <div class="space-y-1">
                <x-responsive-nav-link :href="route($user->dashboardRouteName())" :active="request()->routeIs($user->dashboardRouteName())">Beranda</x-responsive-nav-link>

                @if ($isAdmin)
                    <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">Akademik</div>
                    <x-responsive-nav-link :href="route('admin.data.index', ['resource' => 'academic-years'])" :active="request()->route('resource') === 'academic-years'">Tahun Ajaran</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.data.index', ['resource' => 'classes'])" :active="request()->route('resource') === 'classes'">Kelas</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.data.index', ['resource' => 'subjects'])" :active="request()->route('resource') === 'subjects'">Mata Pelajaran</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.data.index', ['resource' => 'timetables'])" :active="request()->route('resource') === 'timetables'">Jadwal</x-responsive-nav-link>

                    <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">Pengguna</div>
                    <x-responsive-nav-link :href="route('admin.data.index', ['resource' => 'accounts'])" :active="request()->route('resource') === 'accounts'">Akun</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.data.index', ['resource' => 'students'])" :active="request()->route('resource') === 'students'">Murid</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.data.index', ['resource' => 'teachers'])" :active="request()->route('resource') === 'teachers'">Guru</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.data.index', ['resource' => 'parents'])" :active="request()->route('resource') === 'parents'">Orang Tua</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.data.index', ['resource' => 'parent-links'])" :active="request()->route('resource') === 'parent-links'">Hubungan Orang Tua dan Anak</x-responsive-nav-link>

                    <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">PPDB</div>
                    <x-responsive-nav-link :href="route('admin.ppdb.index')" :active="request()->routeIs('admin.ppdb.*')">Kelola Pendaftaran</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('ppdb.information')" :active="request()->routeIs('ppdb.information')">Informasi PPDB</x-responsive-nav-link>
                    <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">Layanan</div>
                    <x-responsive-nav-link :href="route('admin.attendance.settings')" :active="request()->routeIs('admin.attendance.*')">Absensi</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.payments.index')" :active="request()->routeIs('admin.payments.*')">Keuangan (Pembayaran)</x-responsive-nav-link>

                    <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">Konten</div>
                    <x-responsive-nav-link :href="route('admin.data.index', ['resource' => 'announcements'])" :active="request()->route('resource') === 'announcements'">Pengumuman dan Berita</x-responsive-nav-link>
                    <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">Profil dan Konten Publik</div>
                    <x-responsive-nav-link :href="route('admin.school-content.profile.edit')" :active="request()->routeIs('admin.school-content.profile.*')">Profil Sekolah</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.school-content.facilities')" :active="request()->routeIs('admin.school-content.facilities*')">Fasilitas</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.school-content.gallery')" :active="request()->routeIs('admin.school-content.gallery*')">Galeri</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('public.announcements')" :active="request()->routeIs('public.announcements', 'public.announcement')">Berita Sekolah</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('public.teachers')" :active="request()->routeIs('public.teachers')">Daftar Guru</x-responsive-nav-link>

                    <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">Administrasi</div>
                    <x-responsive-nav-link :href="route('admin.audit.index')" :active="request()->routeIs('admin.audit.*')">Aktivitas</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.exports.index')" :active="request()->routeIs('admin.exports.*')">Ekspor</x-responsive-nav-link>
                @elseif ($isApplicant)
                    <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">PPDB</div>
                    <x-responsive-nav-link :href="route('pendaftar.dashboard')" :active="request()->routeIs('pendaftar.*')">Pendaftaran Saya</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('ppdb.information')" :active="request()->routeIs('ppdb.information')">Informasi PPDB</x-responsive-nav-link>
                @else
                    <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">Akademik</div>
                    <x-responsive-nav-link :href="route($user->role.'.timetables.index')" :active="request()->routeIs('*.timetables.*')">Jadwal Pelajaran</x-responsive-nav-link>
                    @if ($isTeacher)
                        <x-responsive-nav-link :href="route('guru.assignments.index')" :active="request()->routeIs('guru.assignments.*')">Tugas dan Penilaian</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('guru.classes.index')" :active="request()->routeIs('guru.classes.*')">Kelas Saya</x-responsive-nav-link>
                        <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">Absensi dan Laporan</div>
                        <x-responsive-nav-link :href="route('guru.attendance.index')" :active="request()->routeIs('guru.attendance.*')">Absensi</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('guru.exports.index')" :active="request()->routeIs('guru.exports.*')">Ekspor Kelas</x-responsive-nav-link>
                        <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">PPDB</div>
                        <x-responsive-nav-link :href="route('ppdb.information')" :active="request()->routeIs('ppdb.information')">Informasi PPDB</x-responsive-nav-link>
                    @elseif ($isStudent)
                        <x-responsive-nav-link :href="route('siswa.assignments.index')" :active="request()->routeIs('siswa.assignments.*')">Tugas</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('siswa.student-record.index')" :active="request()->routeIs('siswa.student-record.*')">Data Saya</x-responsive-nav-link>
                        <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">Layanan</div>
                        <x-responsive-nav-link :href="route('siswa.attendance.index')" :active="request()->routeIs('siswa.attendance.*')">Absensi</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('siswa.payments.index')" :active="request()->routeIs('siswa.payments.*')">Tagihan</x-responsive-nav-link>
                        <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">PPDB</div>
                        <x-responsive-nav-link :href="route('ppdb.information')" :active="request()->routeIs('ppdb.information')">Informasi PPDB</x-responsive-nav-link>
                        @if ($hasAcceptedPpdb)
                            <x-responsive-nav-link :href="route('pendaftar.dashboard')" :active="request()->routeIs('pendaftar.*')">Status PPDB</x-responsive-nav-link>
                        @endif
                    @else
                        <x-responsive-nav-link :href="route('orang_tua.children.index')" :active="request()->routeIs('orang_tua.children.*')">Data Anak</x-responsive-nav-link>
                        <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">Keuangan</div>
                        <x-responsive-nav-link :href="route('orang_tua.payments.index')" :active="request()->routeIs('orang_tua.payments.*')">Pembayaran Anak</x-responsive-nav-link>
                        <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">PPDB</div>
                        <x-responsive-nav-link :href="route('ppdb.information')" :active="request()->routeIs('ppdb.information')">Informasi PPDB</x-responsive-nav-link>
                    @endif
                @endif

                @unless ($isAdmin)
                    <div class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-muhammadiyah-kuning">Konten</div>
                    <x-responsive-nav-link :href="route('public.teachers')" :active="request()->routeIs('public.teachers')">Daftar Guru</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('public.announcements')" :active="request()->routeIs('public.announcements', 'public.announcement')">Berita</x-responsive-nav-link>
                @endunless
            </div>

            <div class="mt-4 border-t border-muhammadiyah-hijau pt-4">
                <div class="px-3">
                    <div class="break-words font-medium text-white">{{ $user->name }}</div>
                    <div class="break-all text-sm font-medium text-white/75">{{ $user->email }}</div>
                </div>
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">Notifikasi ({{ $unreadNotificationCount }})</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('profile.edit')">Profil</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('legal.privacy')" :active="request()->routeIs('legal.privacy')">Kebijakan Privasi</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('legal.terms')" :active="request()->routeIs('legal.terms')">Syarat Penggunaan</x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Keluar</x-responsive-nav-link>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>
