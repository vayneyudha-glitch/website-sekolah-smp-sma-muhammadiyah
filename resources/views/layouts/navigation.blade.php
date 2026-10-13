@auth
    @php
        $user = Auth::user();
        $isAdmin = $user->role === \App\Models\User::ROLE_ADMIN;
        $isTeacher = $user->role === \App\Models\User::ROLE_GURU;
        $isStudent = $user->role === \App\Models\User::ROLE_SISWA;
        $isParent = $user->role === \App\Models\User::ROLE_ORANG_TUA;
        $isApplicant = $user->role === \App\Models\User::ROLE_PENDAFTAR;
        $hasAcceptedPpdb = $isStudent && $user->ppdbApplication()->where('status', 'accepted')->exists();
        $unreadNotificationCount = $user->unreadNotifications()->count();
        $isRouteActive = fn (...$routes) => request()->routeIs(...$routes);
        $isAdminResourceActive = fn ($resources) => request()->routeIs('admin.data.*')
            && in_array(request()->route('resource'), $resources, true);
        $navigationGroups = [];

        if ($isAdmin) {
            $navigationGroups = [
                ['key' => 'utama', 'label' => 'Utama', 'icon' => 'home', 'items' => [
                    ['label' => 'Beranda', 'url' => route($user->dashboardRouteName()), 'active' => $isRouteActive($user->dashboardRouteName())],
                ]],
                ['key' => 'akademik', 'label' => 'Akademik', 'icon' => 'book', 'items' => [
                    ['label' => 'Tahun Ajaran', 'url' => route('admin.data.index', ['resource' => 'academic-years']), 'active' => $isAdminResourceActive(['academic-years'])],
                    ['label' => 'Kelas', 'url' => route('admin.data.index', ['resource' => 'classes']), 'active' => $isAdminResourceActive(['classes'])],
                    ['label' => 'Mata Pelajaran', 'url' => route('admin.data.index', ['resource' => 'subjects']), 'active' => $isAdminResourceActive(['subjects'])],
                    ['label' => 'Jadwal', 'url' => route('admin.data.index', ['resource' => 'timetables']), 'active' => $isAdminResourceActive(['timetables'])],
                    ['label' => 'Kenaikan Kelas', 'url' => route('admin.school-years.advance.index'), 'active' => $isRouteActive('admin.school-years.advance.*')],
                    ['label' => 'Nilai dan Rapor', 'url' => route('admin.reports.index'), 'active' => $isRouteActive('admin.reports.*')],
                ]],
                ['key' => 'pengguna', 'label' => 'Pengguna', 'icon' => 'users', 'items' => [
                    ['label' => 'Akun', 'url' => route('admin.data.index', ['resource' => 'accounts']), 'active' => $isAdminResourceActive(['accounts'])],
                    ['label' => 'Murid', 'url' => route('admin.data.index', ['resource' => 'students']), 'active' => $isAdminResourceActive(['students'])],
                    ['label' => 'Guru', 'url' => route('admin.data.index', ['resource' => 'teachers']), 'active' => $isAdminResourceActive(['teachers'])],
                    ['label' => 'Orang Tua', 'url' => route('admin.data.index', ['resource' => 'parents']), 'active' => $isAdminResourceActive(['parents'])],
                    ['label' => 'Hubungan Orang Tua dan Anak', 'url' => route('admin.data.index', ['resource' => 'parent-links']), 'active' => $isAdminResourceActive(['parent-links'])],
                    ['label' => 'Impor Data Excel', 'url' => route('admin.imports.index'), 'active' => $isRouteActive('admin.imports.*')],
                ]],
                ['key' => 'ppdb', 'label' => 'PPDB', 'icon' => 'clipboard', 'items' => [
                    ['label' => 'Kelola Pendaftaran', 'url' => route('admin.ppdb.index'), 'active' => $isRouteActive('admin.ppdb.*')],
                    ['label' => 'Pengaturan PPDB', 'url' => route('admin.ppdb.settings'), 'active' => $isRouteActive('admin.ppdb.settings')],
                    ['label' => 'Informasi PPDB', 'url' => route('ppdb.information'), 'active' => $isRouteActive('ppdb.information')],
                ]],
                ['key' => 'kehadiran', 'label' => 'Kehadiran', 'icon' => 'calendar', 'items' => [
                    ['label' => 'Absensi', 'url' => route('admin.attendance.settings'), 'active' => $isRouteActive('admin.attendance.settings')],
                    ['label' => 'Tinjauan Absensi', 'url' => route('admin.attendance.review'), 'active' => $isRouteActive('admin.attendance.review*')],
                    ['label' => 'Permohonan Izin/Sakit', 'url' => route('admin.excuses.index'), 'active' => $isRouteActive('admin.excuses.*')],
                ]],
                ['key' => 'keuangan', 'label' => 'Keuangan', 'icon' => 'currency', 'items' => [
                    ['label' => 'Pembayaran', 'url' => route('admin.payments.index'), 'active' => $isRouteActive('admin.payments.*')],
                    ['label' => 'Jenis Tagihan', 'url' => route('admin.payments.types'), 'active' => $isRouteActive('admin.payments.types*')],
                ]],
                ['key' => 'konten', 'label' => 'Konten', 'icon' => 'megaphone', 'items' => [
                    ['label' => 'Pengumuman dan Berita', 'url' => route('admin.data.index', ['resource' => 'announcements']), 'active' => $isAdminResourceActive(['announcements'])],
                    ['label' => 'Berita Sekolah', 'url' => route('public.announcements'), 'active' => $isRouteActive('public.announcements', 'public.announcement')],
                    ['label' => 'Daftar Guru', 'url' => route('public.teachers'), 'active' => $isRouteActive('public.teachers')],
                    ['label' => 'Profil Sekolah', 'url' => route('admin.school-content.profile.edit'), 'active' => $isRouteActive('admin.school-content.profile.*')],
                    ['label' => 'Fasilitas', 'url' => route('admin.school-content.facilities'), 'active' => $isRouteActive('admin.school-content.facilities*')],
                    ['label' => 'Galeri', 'url' => route('admin.school-content.gallery'), 'active' => $isRouteActive('admin.school-content.gallery*')],
                ]],
                ['key' => 'sistem', 'label' => 'Sistem', 'icon' => 'settings', 'items' => [
                    ['label' => 'Aktivitas Audit', 'url' => route('admin.audit.index'), 'active' => $isRouteActive('admin.audit.*')],
                    ['label' => 'Pusat Ekspor', 'url' => route('admin.exports.index'), 'active' => $isRouteActive('admin.exports.*')],
                    ['label' => 'Bantuan', 'url' => route('help.show'), 'active' => $isRouteActive('help.show')],
                ]],
            ];
        } elseif ($isApplicant) {
            $navigationGroups = [
                ['key' => 'utama', 'label' => 'Utama', 'icon' => 'home', 'items' => [
                    ['label' => 'Beranda', 'url' => route($user->dashboardRouteName()), 'active' => $isRouteActive($user->dashboardRouteName())],
                ]],
                ['key' => 'ppdb', 'label' => 'PPDB', 'icon' => 'clipboard', 'items' => [
                    ['label' => 'Pendaftaran Saya', 'url' => route('pendaftar.dashboard'), 'active' => $isRouteActive('pendaftar.*')],
                    ['label' => 'Informasi PPDB', 'url' => route('ppdb.information'), 'active' => $isRouteActive('ppdb.information')],
                ]],
            ];
        } else {
            $navigationGroups = [
                ['key' => 'utama', 'label' => 'Utama', 'icon' => 'home', 'items' => [
                    ['label' => 'Beranda', 'url' => route($user->dashboardRouteName()), 'active' => $isRouteActive($user->dashboardRouteName())],
                ]],
                ['key' => 'akademik', 'label' => 'Akademik', 'icon' => 'book', 'items' => [
                    ['label' => 'Jadwal Pelajaran', 'url' => route($user->role.'.timetables.index'), 'active' => $isRouteActive('*.timetables.*')],
                    ...($isTeacher ? [
                        ['label' => 'Tugas dan Penilaian', 'url' => route('guru.assignments.index'), 'active' => $isRouteActive('guru.assignments.*')],
                        ['label' => 'Kelas Saya', 'url' => route('guru.classes.index'), 'active' => $isRouteActive('guru.classes.*')],
                    ] : []),
                    ...($isStudent ? [
                        ['label' => 'Tugas', 'url' => route('siswa.assignments.index'), 'active' => $isRouteActive('siswa.assignments.*')],
                        ['label' => 'Data Saya', 'url' => route('siswa.student-record.index'), 'active' => $isRouteActive('siswa.student-record.*')],
                    ] : []),
                    ...($isParent ? [
                        ['label' => 'Data Anak', 'url' => route('orang_tua.children.index'), 'active' => $isRouteActive('orang_tua.children.*')],
                    ] : []),
                ]],
            ];

            if ($isTeacher) {
                $navigationGroups[] = ['key' => 'kehadiran', 'label' => 'Kehadiran', 'icon' => 'calendar', 'items' => [
                    ['label' => 'Absensi', 'url' => route('guru.attendance.index'), 'active' => $isRouteActive('guru.attendance.*')],
                    ['label' => 'Permohonan Izin/Sakit', 'url' => route('guru.excuses.index'), 'active' => $isRouteActive('guru.excuses.*')],
                ]];
                $navigationGroups[] = ['key' => 'nilai', 'label' => 'Nilai dan Rapor', 'icon' => 'book', 'items' => [
                    ['label' => 'Nilai dan Rapor', 'url' => route('guru.reports.index'), 'active' => $isRouteActive('guru.reports.*')],
                    ['label' => 'Ekspor Kelas', 'url' => route('guru.exports.index'), 'active' => $isRouteActive('guru.exports.*')],
                ]];
            } elseif ($isStudent) {
                $navigationGroups[] = ['key' => 'layanan', 'label' => 'Layanan', 'icon' => 'calendar', 'items' => [
                    ['label' => 'Absensi', 'url' => route('siswa.attendance.index'), 'active' => $isRouteActive('siswa.attendance.*')],
                    ['label' => 'Permohonan Izin/Sakit', 'url' => route('siswa.excuses.index'), 'active' => $isRouteActive('siswa.excuses.*')],
                    ['label' => 'Rapor Saya', 'url' => route('siswa.reports.index'), 'active' => $isRouteActive('siswa.reports.*')],
                    ['label' => 'Tagihan', 'url' => route('siswa.payments.index'), 'active' => $isRouteActive('siswa.payments.*')],
                ]];
            } else {
                $navigationGroups[] = ['key' => 'kehadiran', 'label' => 'Kehadiran', 'icon' => 'calendar', 'items' => [
                    ['label' => 'Izin/Sakit Anak', 'url' => route('orang_tua.excuses.index'), 'active' => $isRouteActive('orang_tua.excuses.*')],
                ]];
                $navigationGroups[] = ['key' => 'keuangan', 'label' => 'Keuangan', 'icon' => 'currency', 'items' => [
                    ['label' => 'Keuangan', 'url' => route('orang_tua.payments.index'), 'active' => $isRouteActive('orang_tua.payments.*')],
                ]];
            }

            if ($isTeacher || $isStudent) {
                $navigationGroups[] = ['key' => 'ppdb', 'label' => 'PPDB', 'icon' => 'clipboard', 'items' => array_merge(
                    [['label' => 'Informasi PPDB', 'url' => route('ppdb.information'), 'active' => $isRouteActive('ppdb.information')]],
                    $hasAcceptedPpdb ? [['label' => 'Status PPDB', 'url' => route('pendaftar.dashboard'), 'active' => $isRouteActive('pendaftar.*')]] : [],
                )];
            } elseif ($isParent) {
                $navigationGroups[] = ['key' => 'ppdb', 'label' => 'PPDB', 'icon' => 'clipboard', 'items' => [
                    ['label' => 'Informasi PPDB', 'url' => route('ppdb.information'), 'active' => $isRouteActive('ppdb.information')],
                ]];
            }

            $navigationGroups[] = ['key' => 'konten', 'label' => 'Konten', 'icon' => 'megaphone', 'items' => [
                ['label' => 'Daftar Guru', 'url' => route('public.teachers'), 'active' => $isRouteActive('public.teachers')],
                ['label' => 'Berita Sekolah', 'url' => route('public.announcements'), 'active' => $isRouteActive('public.announcements', 'public.announcement')],
            ]];
        }

        $activeGroupIds = collect($navigationGroups)
            ->filter(fn ($group) => collect($group['items'])->contains(fn ($item) => $item['active']))
            ->pluck('key')
            ->values()
            ->all();
        $navigationGroupKeys = collect($navigationGroups)->pluck('key')->values()->all();
    @endphp

    <style>
        [x-cloak] { display: none !important; }
        [data-app-sidebar] { transform: translateX(-100%); }
        [data-app-sidebar].is-open { transform: translateX(0); }
        @media (min-width: 1024px) {
            [data-app-sidebar] { transform: translateX(0) !important; }
        }
    </style>

    <div
        x-data="{
            drawerOpen: false,
            profileOpen: false,
            expandedGroups: @js($activeGroupIds),
            activeGroups: @js($activeGroupIds),
            initialize() {
                try {
                    const stored = JSON.parse(localStorage.getItem('school-nav-{{ $user->role }}') || '{}');
                    if (stored && typeof stored === 'object' && !Array.isArray(stored)) {
                        Object.entries(stored).forEach(([key, isOpen]) => {
                            if (typeof isOpen === 'boolean' && !this.activeGroups.includes(key)) {
                                this.expandedGroups = this.expandedGroups.filter((group) => group !== key);
                                if (isOpen) this.expandedGroups.push(key);
                            }
                        });
                    }
                    this.activeGroups.forEach((key) => {
                        if (!this.expandedGroups.includes(key)) this.expandedGroups.push(key);
                    });
                } catch (error) {}
            },
            isExpanded(key) {
                return this.expandedGroups.includes(key);
            },
            toggleGroup(key) {
                if (this.isExpanded(key)) {
                    this.expandedGroups = this.expandedGroups.filter((group) => group !== key);
                } else {
                    this.expandedGroups.push(key);
                }
                try {
                    const state = {};
                    @js($navigationGroupKeys).forEach((key) => { state[key] = this.isExpanded(key); });
                    localStorage.setItem('school-nav-{{ $user->role }}', JSON.stringify(state));
                } catch (error) {}
            }
        }"
        @keydown.escape.window="drawerOpen = false; profileOpen = false"
    >
        <div
            x-cloak
            x-show="drawerOpen"
            x-transition.opacity
            @click="drawerOpen = false"
            class="fixed inset-0 z-40 bg-gray-950/60 lg:hidden"
            aria-hidden="true"
        ></div>

        <aside
            data-app-sidebar
            :class="drawerOpen ? 'is-open' : ''"
            class="fixed inset-y-0 left-0 z-50 flex w-[260px] flex-col border-r border-white/10 bg-muhammadiyah-hijau-tua text-white shadow-xl transition-transform duration-200"
            aria-label="Navigasi utama"
        >
            <div class="flex min-h-[76px] items-center justify-between gap-3 border-b border-white/15 px-5">
                <a href="{{ route($user->dashboardRouteName()) }}" class="flex min-w-0 items-center gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-muhammadiyah-kuning">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white p-1.5">
                        <x-application-logo />
                    </span>
                    <span class="min-w-0 text-xs font-bold leading-5 text-white">SMP SMA Muhammadiyah Subang</span>
                </a>
                <button
                    type="button"
                    @click="drawerOpen = false"
                    class="rounded-lg p-2 text-white hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-muhammadiyah-kuning lg:hidden"
                    aria-label="Tutup menu navigasi"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round" stroke-width="2" /></svg>
                </button>
            </div>

            <nav class="min-h-0 flex-1 space-y-3 overflow-y-auto px-3 py-4" aria-label="Menu panel">
                @foreach ($navigationGroups as $group)
                    <section>
                        <button
                            type="button"
                            @click="toggleGroup('{{ $group['key'] }}')"
                            :aria-expanded="isExpanded('{{ $group['key'] }}').toString()"
                            class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-left text-xs font-bold uppercase tracking-wider text-white/75 transition hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-muhammadiyah-kuning"
                        >
                            <svg class="h-4 w-4 shrink-0 text-muhammadiyah-kuning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                @switch ($group['icon'])
                                    @case('home') <path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z" /> @break
                                    @case('book') <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21zM4 5.5v13A2.5 2.5 0 0 1 6.5 16H20" /> @break
                                    @case('users') <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6m3-3h-6" /> @break
                                    @case('clipboard') <path d="M9 4h6l1 2h3v15H5V6h3zM9 4a2 2 0 0 1 6 0M9 12h6m-6 4h6" /> @break
                                    @case('calendar') <path d="M4 5h16v16H4zM8 3v4m8-4v4M4 10h16m-11 4h2m3 0h2m-7 3h2" /> @break
                                    @case('currency') <path d="M12 2v20m5-16H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" /> @break
                                    @case('megaphone') <path d="m3 11 18-5v12L3 13zm0 2 2 7h4l-2-6m14-6a5 5 0 0 1 0 10" /> @break
                                    @default <path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8ZM4 13l-2 1 2 4 2-1a7 7 0 0 0 2 1l.5 2h5l.5-2a7 7 0 0 0 2-1l2 1 2-4-2-1a7 7 0 0 0 0-2l2-1-2-4-2 1a7 7 0 0 0-2-1L13 4H8l-.5 2a7 7 0 0 0-2 1l-2-1-2 4 2 1a7 7 0 0 0 0 2Z" />
                                @endswitch
                            </svg>
                            <span class="min-w-0 flex-1 truncate">{{ $group['label'] }}</span>
                            <svg class="h-4 w-4 shrink-0 transition-transform" :class="isExpanded('{{ $group['key'] }}') ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" /></svg>
                        </button>
                        <div x-cloak x-show="isExpanded('{{ $group['key'] }}')" class="mt-1 space-y-1">
                            @foreach ($group['items'] as $item)
                                <a
                                    href="{{ $item['url'] }}"
                                    @if ($item['active']) aria-current="page" @endif
                                    @click="drawerOpen = false"
                                    class="flex min-h-10 items-center rounded-r-lg border-l-[3px] px-3 py-2 text-sm leading-5 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-muhammadiyah-kuning {{ $item['active'] ? 'border-muhammadiyah-kuning bg-muhammadiyah-hijau text-white font-semibold' : 'border-transparent text-white/85 hover:border-white/40 hover:bg-white/10 hover:text-white' }}"
                                >{{ $item['label'] }}</a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </nav>
            <div class="border-t border-white/15 px-5 py-3 text-xs text-white/65">Panel {{ $user->role }}</div>
        </aside>

        <header class="sticky top-0 z-30 flex min-h-16 items-center justify-between gap-2 border-b border-muhammadiyah-hijau bg-muhammadiyah-hijau-tua px-3 text-white shadow-sm sm:px-5 lg:ml-[260px] lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <button
                    type="button"
                    @click="drawerOpen = true"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-white hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-muhammadiyah-kuning lg:hidden"
                    aria-label="Buka menu navigasi"
                    :aria-expanded="drawerOpen.toString()"
                >
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-width="2" /></svg>
                </button>
                <div class="min-w-0 truncate text-base font-semibold sm:text-lg">
                    @if ($pageHeader)
                        {{ $pageHeader }}
                    @else
                        Panel Sekolah
                    @endif
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                <a
                    href="{{ route('notifications.index') }}"
                    class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg text-white hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-muhammadiyah-kuning"
                    aria-label="Notifikasi, {{ $unreadNotificationCount }} belum dibaca"
                    title="Notifikasi"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12h4" /></svg>
                    <span class="absolute right-0.5 top-0.5 min-w-4 rounded-full bg-muhammadiyah-kuning px-1 text-center text-[10px] font-bold leading-4 text-muhammadiyah-hijau-tua">{{ $unreadNotificationCount }}</span>
                </a>

                <div class="relative">
                    <button
                        type="button"
                        @click="profileOpen = !profileOpen"
                        @click.outside="profileOpen = false"
                        :aria-expanded="profileOpen.toString()"
                        class="flex max-w-[10rem] items-center gap-2 rounded-lg px-2 py-2 text-sm font-semibold text-white hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-muhammadiyah-kuning sm:max-w-[14rem] sm:px-3"
                    >
                        <span class="max-w-[8ch] truncate sm:max-w-[14ch]" title="{{ $user->name }}">{{ $user->name }}</span>
                        <svg class="h-4 w-4 shrink-0 transition-transform" :class="profileOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" /></svg>
                    </button>
                    <div x-cloak x-show="profileOpen" x-transition.origin.top.right class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 text-sm text-gray-800 shadow-xl">
                        <div class="border-b border-gray-100 px-4 py-3">
                            <div class="truncate font-semibold text-muhammadiyah-hijau-tua" title="{{ $user->name }}">{{ $user->name }}</div>
                            <div class="truncate text-xs text-gray-600">{{ $user->email }}</div>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 hover:bg-green-50 focus-visible:bg-green-50 focus-visible:outline-none">Profil</a>
                        <a href="{{ route('help.show') }}" class="block px-4 py-2.5 hover:bg-green-50 focus-visible:bg-green-50 focus-visible:outline-none">Bantuan</a>
                        <a href="{{ route('legal.privacy') }}" class="block px-4 py-2.5 hover:bg-green-50 focus-visible:bg-green-50 focus-visible:outline-none">Kebijakan Privasi</a>
                        <a href="{{ route('legal.terms') }}" class="block px-4 py-2.5 hover:bg-green-50 focus-visible:bg-green-50 focus-visible:outline-none">Syarat Penggunaan</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2.5 text-left text-red-700 hover:bg-red-50 focus-visible:bg-red-50 focus-visible:outline-none">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>
    </div>
@endauth

@guest
    <nav class="border-b border-muhammadiyah-hijau bg-muhammadiyah-hijau-tua text-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('public.home') }}" class="rounded-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-muhammadiyah-kuning">SMP SMA Muhammadiyah Subang</a>
            <div class="flex flex-wrap items-center gap-2 text-sm font-semibold">
                <a href="{{ route('public.home') }}" class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-muhammadiyah-kuning">Beranda</a>
                <a href="{{ route('ppdb.information') }}" class="rounded-lg px-3 py-2 hover:bg-muhammadiyah-hijau focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-muhammadiyah-kuning">PPDB</a>
                <a href="{{ route('login') }}" class="rounded-lg bg-muhammadiyah-kuning px-3 py-2 text-muhammadiyah-hijau-tua hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">Login</a>
            </div>
        </div>
    </nav>
@endguest
