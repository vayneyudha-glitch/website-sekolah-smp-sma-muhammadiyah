<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminPpdbController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\AdminSchoolContentController;
use App\Http\Controllers\AdminImportController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ExcuseRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PpdbController;
use App\Http\Controllers\PublicSchoolController;
use App\Http\Controllers\SchoolDataController;
use App\Http\Controllers\SchoolPortalController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\SchoolExportController;
use App\Http\Controllers\SchoolYearAdvancementController;
use App\Http\Controllers\ReportCardController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/pembayaran/webhook/{provider}', PaymentWebhookController::class)
    ->whereIn('provider', ['midtrans', 'xendit'])
    ->middleware('throttle:120,1')
    ->name('payments.webhook');

Route::get('/', [PublicSchoolController::class, 'home'])->name('public.home');
Route::get('/tentang-sekolah', [PublicSchoolController::class, 'page'])->defaults('page', 'tentang')->name('public.about');
Route::get('/visi-misi', [PublicSchoolController::class, 'page'])->defaults('page', 'visi-misi')->name('public.vision-mission');
Route::get('/sejarah', [PublicSchoolController::class, 'page'])->defaults('page', 'sejarah')->name('public.history');
Route::get('/fasilitas', [PublicSchoolController::class, 'facilities'])->name('public.facilities');
Route::get('/galeri', [PublicSchoolController::class, 'gallery'])->name('public.gallery');
Route::get('/kontak', [PublicSchoolController::class, 'contact'])->name('public.contact');
Route::get('/jenjang/smp', [PublicSchoolController::class, 'page'])->defaults('page', 'jenjang/smp')->name('public.level.smp');
Route::get('/jenjang/sma', [PublicSchoolController::class, 'page'])->defaults('page', 'jenjang/sma')->name('public.level.sma');
Route::get('/ppdb', [PpdbController::class, 'information'])->name('ppdb.information');
Route::middleware('guest')->group(function () {
    Route::get('/ppdb/daftar', [PpdbController::class, 'createAccount'])->name('ppdb.register.create');
    Route::post('/ppdb/daftar', [PpdbController::class, 'register'])->middleware('throttle:10,1')->name('ppdb.register.store');
});
Route::get('/guru', [PublicSchoolController::class, 'teachers'])->name('public.teachers');
Route::get('/berita', [PublicSchoolController::class, 'announcements'])->name('public.announcements');
Route::get('/berita/{announcement:slug}', [PublicSchoolController::class, 'announcement'])->name('public.announcement');
Route::view('/kebijakan-privasi', 'legal.privacy')->name('legal.privacy');
Route::view('/syarat-penggunaan', 'legal.terms')->name('legal.terms');

Route::middleware('auth')->prefix('pendaftar')->name('pendaftar.')->group(function () {
    Route::get('/dashboard', [PpdbController::class, 'dashboard'])->name('dashboard');
    Route::put('/data-murid', [PpdbController::class, 'updateStudent'])->name('student.update');
    Route::put('/data-wali', [PpdbController::class, 'updateGuardian'])->name('guardian.update');
    Route::post('/berkas', [PpdbController::class, 'uploadDocuments'])->middleware('throttle:10,1')->name('documents.upload');
    Route::post('/kirim', [PpdbController::class, 'submit'])->name('submit');
    Route::get('/bukti-pendaftaran', [PpdbController::class, 'receipt'])->name('receipt');
});
Route::middleware('auth')->get('/ppdb/berkas/{document}/unduh', [PpdbController::class, 'downloadDocument'])
    ->whereNumber('document')->name('ppdb.documents.download');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/absensi/tantangan', [AttendanceController::class, 'issueChallenge'])
        ->middleware('throttle:attendance')->name('attendance.challenge');
    Route::get('/dashboard', function (Request $request) {
        return redirect(route($request->user()->dashboardRouteName(), absolute: false));
    })->name('dashboard');

    foreach ([
        User::ROLE_ADMIN => 'Admin',
        User::ROLE_GURU => 'Guru',
        User::ROLE_SISWA => 'Siswa',
        User::ROLE_ORANG_TUA => 'Orang Tua',
    ] as $role => $title) {
        Route::prefix($role)->name($role.'.')->middleware('role:'.$role)->group(function () use ($role) {
            Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');

            if (in_array($role, [User::ROLE_GURU, User::ROLE_SISWA, User::ROLE_ORANG_TUA], true)) {
                Route::get('/jadwal', [TimetableController::class, 'index'])->name('timetables.index');
            }

            if ($role === User::ROLE_GURU) {
                Route::get('/tugas', [AssignmentController::class, 'index'])->name('assignments.index');
                Route::post('/tugas', [AssignmentController::class, 'store'])->middleware('throttle:10,1')->name('assignments.store');
                Route::get('/tugas/ekspor/{schoolClass}', [AssignmentController::class, 'exportClass'])->whereNumber('schoolClass')->name('assignments.export');
                Route::get('/tugas/rekap/{schoolClass}', [AssignmentController::class, 'classReport'])->whereNumber('schoolClass')->name('assignments.class-report');
                Route::get('/tugas/{assignment}', [AssignmentController::class, 'show'])->whereNumber('assignment')->name('assignments.show');
                Route::put('/tugas/pengumpulan/{submission}/nilai', [AssignmentController::class, 'grade'])->whereNumber('submission')->name('assignments.grade');
                Route::get('/kelas', [SchoolPortalController::class, 'teacherClasses'])->name('classes.index');
                Route::get('/absensi', [AttendanceController::class, 'teacher'])->name('attendance.index');
                Route::post('/absensi/masuk', [AttendanceController::class, 'teacherCheckIn'])->middleware('throttle:attendance')->name('attendance.check-in');
                Route::post('/absensi/pulang', [AttendanceController::class, 'teacherCheckOut'])->middleware('throttle:attendance')->name('attendance.check-out');
                Route::get('/kelas/{schoolClass}/absensi', [AttendanceController::class, 'classAttendance'])->name('classes.attendance');
                Route::put('/kelas/{schoolClass}/absensi/{student}/koreksi', [AttendanceController::class, 'correctStudent'])->name('classes.attendance.correct');
                Route::get('/izin', [ExcuseRequestController::class, 'index'])->name('excuses.index');
                Route::put('/izin/{excuse}', [ExcuseRequestController::class, 'review'])->whereNumber('excuse')->name('excuses.review');
                Route::get('/rapor', [ReportCardController::class, 'index'])->name('reports.index');
                Route::get('/rapor/kelas/{schoolClass}', [ReportCardController::class, 'homeroom'])->name('reports.homeroom');
                Route::get('/rapor/kelas/{schoolClass}/mapel/{subject}', [ReportCardController::class, 'manage'])->name('reports.manage');
                Route::post('/rapor/kelas/{schoolClass}/mapel/{subject}/komponen', [ReportCardController::class, 'saveComponents'])->name('reports.components');
                Route::post('/rapor/kelas/{schoolClass}/mapel/{subject}/nilai', [ReportCardController::class, 'saveScores'])->name('reports.scores');
                Route::post('/rapor/kelas/{schoolClass}/terbit', [ReportCardController::class, 'saveClassReports'])->name('reports.publish');
            } elseif ($role === User::ROLE_SISWA) {
                Route::get('/tugas', [AssignmentController::class, 'index'])->name('assignments.index');
                Route::post('/tugas/{assignment}/kirim', [AssignmentController::class, 'submit'])->whereNumber('assignment')->middleware('throttle:10,1')->name('assignments.submit');
                Route::get('/data-saya', [SchoolPortalController::class, 'student'])->name('student-record.index');
                Route::get('/absensi', [AttendanceController::class, 'student'])->name('attendance.index');
                Route::post('/absensi', [AttendanceController::class, 'markStudent'])->middleware('throttle:attendance')->name('attendance.store');
                Route::get('/izin', [ExcuseRequestController::class, 'index'])->name('excuses.index');
                Route::get('/izin/buat', [ExcuseRequestController::class, 'create'])->name('excuses.create');
                Route::post('/izin', [ExcuseRequestController::class, 'store'])->middleware('throttle:10,1')->name('excuses.store');
                Route::get('/rapor', [ReportCardController::class, 'index'])->name('reports.index');
                Route::get('/pembayaran', [PaymentController::class, 'index'])->name('payments.index');
                Route::get('/pembayaran/{invoice}', [PaymentController::class, 'show'])->whereNumber('invoice')->name('payments.show');
                Route::post('/pembayaran/{invoice}/bayar', [PaymentController::class, 'start'])->whereNumber('invoice')->name('payments.start');
                Route::get('/pembayaran/riwayat/{payment}/kuitansi', [PaymentController::class, 'receipt'])->whereNumber('payment')->name('payments.receipt');
            } elseif ($role === User::ROLE_ORANG_TUA) {
                Route::get('/anak', [SchoolPortalController::class, 'children'])->name('children.index');
                Route::get('/anak/{student}/nilai', [AssignmentController::class, 'childGrades'])->whereNumber('student')->name('children.assignments');
                Route::get('/anak/{student}/absensi', [AttendanceController::class, 'parentStudent'])->name('children.attendance');
                Route::get('/izin', [ExcuseRequestController::class, 'index'])->name('excuses.index');
                Route::get('/anak/{student}/izin', [ExcuseRequestController::class, 'create'])->whereNumber('student')->name('children.excuses.create');
                Route::post('/anak/{student}/izin', [ExcuseRequestController::class, 'store'])->whereNumber('student')->middleware('throttle:10,1')->name('children.excuses.store');
                Route::get('/anak/{student}/rapor', [ReportCardController::class, 'studentReports'])->whereNumber('student')->name('children.reports');
                Route::get('/pembayaran', [PaymentController::class, 'index'])->name('payments.index');
                Route::get('/pembayaran/{invoice}', [PaymentController::class, 'show'])->whereNumber('invoice')->name('payments.show');
                Route::post('/pembayaran/{invoice}/bayar', [PaymentController::class, 'start'])->whereNumber('invoice')->name('payments.start');
                Route::get('/pembayaran/riwayat/{payment}/kuitansi', [PaymentController::class, 'receipt'])->whereNumber('payment')->name('payments.receipt');
            }
        });
    }

    Route::get('/absensi/berkas/{attendance}', [AttendanceController::class, 'privateAttachment'])
        ->whereNumber('attendance')->name('attendance.attachment');
    Route::get('/tugas/{assignment}/lampiran', [AssignmentController::class, 'downloadAssignment'])
        ->whereNumber('assignment')->name('assignments.attachment');
    Route::get('/tugas/pengumpulan/{submission}/lampiran', [AssignmentController::class, 'downloadSubmission'])
        ->whereNumber('submission')->name('assignments.submission-attachment');
    Route::get('/izin/{excuse}/lampiran', [ExcuseRequestController::class, 'attachment'])
        ->whereNumber('excuse')->name('excuses.attachment');
    Route::get('/rapor/{report}/pdf', [ReportCardController::class, 'pdf'])
        ->whereNumber('report')->name('reports.pdf');

    Route::prefix('admin')->name('admin.')->middleware('role:'.User::ROLE_ADMIN)->group(function () {
        Route::get('/aktivitas', [SchoolDataController::class, 'auditIndex'])->name('audit.index');
        Route::get('/izin', [ExcuseRequestController::class, 'index'])->name('excuses.index');
        Route::put('/izin/{excuse}', [ExcuseRequestController::class, 'review'])->whereNumber('excuse')->name('excuses.review');
        Route::prefix('impor')->name('imports.')->group(function (): void {
            Route::get('/', [AdminImportController::class, 'index'])->name('index');
            Route::get('/templat/{type}', [AdminImportController::class, 'template'])->whereIn('type', ['students', 'teachers', 'parents', 'classes'])->name('template');
            Route::post('/pratinjau', [AdminImportController::class, 'preview'])->middleware('throttle:10,1')->name('preview.store');
            Route::get('/pratinjau/{batch}', [AdminImportController::class, 'showPreview'])->name('preview');
            Route::post('/pratinjau/{batch}/konfirmasi', [AdminImportController::class, 'confirm'])->name('confirm');
            Route::get('/{batch}/kata-sandi', [AdminImportController::class, 'downloadPasswords'])->name('passwords');
        });
        Route::get('/kenaikan-kelas', [SchoolYearAdvancementController::class, 'index'])->name('school-years.advance.index');
        Route::post('/kenaikan-kelas/pratinjau', [SchoolYearAdvancementController::class, 'preview'])->name('school-years.advance.preview');
        Route::post('/kenaikan-kelas', [SchoolYearAdvancementController::class, 'execute'])->name('school-years.advance.execute');
        Route::get('/rapor', [ReportCardController::class, 'index'])->name('reports.index');
        Route::get('/rapor/kelas/{schoolClass}', [ReportCardController::class, 'homeroom'])->name('reports.homeroom');
        Route::get('/rapor/kelas/{schoolClass}/mapel/{subject}', [ReportCardController::class, 'manage'])->name('reports.manage');
        Route::post('/rapor/kelas/{schoolClass}/mapel/{subject}/komponen', [ReportCardController::class, 'saveComponents'])->name('reports.components');
        Route::post('/rapor/kelas/{schoolClass}/mapel/{subject}/nilai', [ReportCardController::class, 'saveScores'])->name('reports.scores');
        Route::put('/rapor/kelas/{schoolClass}/predikat', [ReportCardController::class, 'saveBands'])->name('reports.bands');
        Route::post('/rapor/kelas/{schoolClass}/terbit', [ReportCardController::class, 'saveClassReports'])->name('reports.publish');
        Route::prefix('profil-sekolah')->name('school-content.')->group(function (): void {
            Route::get('/pengaturan', [AdminSchoolContentController::class, 'editProfile'])->name('profile.edit');
            Route::put('/pengaturan', [AdminSchoolContentController::class, 'updateProfile'])->name('profile.update');
            Route::get('/fasilitas', [AdminSchoolContentController::class, 'facilities'])->name('facilities');
            Route::get('/fasilitas/buat', [AdminSchoolContentController::class, 'createFacility'])->name('facilities.create');
            Route::post('/fasilitas', [AdminSchoolContentController::class, 'storeFacility'])->name('facilities.store');
            Route::get('/fasilitas/{facility}/ubah', [AdminSchoolContentController::class, 'editFacility'])->name('facilities.edit');
            Route::put('/fasilitas/{facility}', [AdminSchoolContentController::class, 'updateFacility'])->name('facilities.update');
            Route::delete('/fasilitas/{facility}', [AdminSchoolContentController::class, 'destroyFacility'])->name('facilities.destroy');
            Route::get('/galeri', [AdminSchoolContentController::class, 'gallery'])->name('gallery');
            Route::post('/galeri', [AdminSchoolContentController::class, 'storeGallery'])->name('gallery.store');
            Route::delete('/galeri/{gallery}', [AdminSchoolContentController::class, 'destroyGallery'])->name('gallery.destroy');
        });
        Route::prefix('pembayaran')->name('payments.')->group(function () {
            Route::get('/', [AdminPaymentController::class, 'index'])->name('index');
            Route::get('/ekspor', [AdminPaymentController::class, 'export'])->name('export');
            Route::get('/jenis', [AdminPaymentController::class, 'types'])->name('types');
            Route::post('/jenis', [AdminPaymentController::class, 'storeType'])->name('types.store');
            Route::put('/jenis/{billType}', [AdminPaymentController::class, 'updateType'])->whereNumber('billType')->name('types.update');
            Route::delete('/jenis/{billType}', [AdminPaymentController::class, 'archiveType'])->whereNumber('billType')->name('types.archive');
            Route::post('/terbitkan', [AdminPaymentController::class, 'issue'])->name('issue');
            Route::post('/tagihan/{invoice}/manual', [AdminPaymentController::class, 'storeManualPayment'])->whereNumber('invoice')->middleware('throttle:10,1')->name('manual');
            Route::post('/tagihan/{invoice}/batalkan', [AdminPaymentController::class, 'cancelInvoice'])->whereNumber('invoice')->name('cancel');
            Route::get('/bukti/{payment}', [AdminPaymentController::class, 'downloadProof'])->whereNumber('payment')->name('proof');
        });
        Route::get('/pengaturan/absensi', [AdminAttendanceController::class, 'settings'])->name('attendance.settings');
        Route::put('/pengaturan/absensi', [AdminAttendanceController::class, 'updateSettings'])->name('attendance.settings.update');
        Route::get('/absensi/perlu-ditinjau', [AdminAttendanceController::class, 'reviewQueue'])->name('attendance.review');
        Route::put('/absensi/perlu-ditinjau/{type}/{attendance}', [AdminAttendanceController::class, 'review'])
            ->whereIn('type', ['murid', 'guru'])->whereNumber('attendance')->name('attendance.review.update');
        Route::get('/absensi/guru', [AdminAttendanceController::class, 'teacherReport'])->name('attendance.teachers');
        Route::get('/absensi/guru/ekspor', [AdminAttendanceController::class, 'exportTeachers'])->name('attendance.teachers.export');
        Route::prefix('ppdb')->name('ppdb.')->group(function () {
            Route::get('/', [AdminPpdbController::class, 'index'])->name('index');
            Route::get('/ekspor', [AdminPpdbController::class, 'export'])->name('export');
            Route::get('/pengaturan', [AdminPpdbController::class, 'settings'])->name('settings');
            Route::put('/pengaturan', [AdminPpdbController::class, 'updateSettings'])->name('settings.update');
            Route::get('/{application}', [AdminPpdbController::class, 'show'])->whereNumber('application')->name('show');
            Route::put('/{application}/keputusan', [AdminPpdbController::class, 'decide'])->whereNumber('application')->name('decide');
            Route::put('/berkas/{document}', [AdminPpdbController::class, 'reviewDocument'])->whereNumber('document')->name('documents.review');
        });

        Route::prefix('data/{resource}')->name('data.')->group(function () {
            Route::get('/', [SchoolDataController::class, 'index'])->name('index');
            Route::get('/buat', [SchoolDataController::class, 'create'])->name('create');
            Route::post('/', [SchoolDataController::class, 'store'])->middleware('throttle:10,1')->name('store');
            Route::get('/{record}/ubah', [SchoolDataController::class, 'edit'])->whereNumber('record')->name('edit');
            Route::put('/{record}', [SchoolDataController::class, 'update'])->whereNumber('record')->middleware('throttle:10,1')->name('update');
            Route::delete('/{record}', [SchoolDataController::class, 'destroy'])->whereNumber('record')->name('destroy');
        });
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/bantuan', fn (Request $request) => view('help.show', ['role' => $request->user()->role]))
        ->name('help.show');
    Route::get('/absensi/guru/{attendance}/selfie/{event}', [AttendanceController::class, 'privateTeacherSelfie'])
        ->whereNumber('attendance')->whereIn('event', ['masuk', 'pulang'])->name('attendance.teacher-selfie');
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifikasi/{notification}/dibaca', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::put('/preferensi-notifikasi', [NotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:'.User::ROLE_ADMIN])->prefix('admin/ekspor')->name('admin.exports.')->group(function (): void {
    Route::get('/', [SchoolExportController::class, 'adminIndex'])->name('index');
    Route::get('/{dataset}/{format}', [SchoolExportController::class, 'admin'])
        ->whereIn('dataset', ['students', 'teachers', 'student-attendance', 'teacher-attendance', 'grades', 'payments', 'ppdb'])
        ->whereIn('format', ['xlsx', 'pdf'])
        ->name('download');
});

Route::middleware(['auth', 'verified', 'role:'.User::ROLE_GURU])->prefix('guru/ekspor')->name('guru.exports.')->group(function (): void {
    Route::get('/', [SchoolExportController::class, 'teacherIndex'])->name('index');
    Route::get('/{dataset}/{format}', [SchoolExportController::class, 'teacher'])
        ->whereIn('dataset', ['attendance', 'grades'])
        ->whereIn('format', ['xlsx', 'pdf'])
        ->name('download');
});

require __DIR__.'/auth.php';
