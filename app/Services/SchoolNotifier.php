<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AssignmentSubmission;
use App\Models\ClassAssignment;
use App\Models\StudentInvoice;
use App\Models\TeacherAttendance;
use App\Models\User;
use App\Notifications\SchoolEventNotification;
use Illuminate\Database\Eloquent\Builder;

class SchoolNotifier
{
    public function announcement(Announcement $announcement): void
    {
        $users = User::query()->where('is_active', true)
            ->when($announcement->audience_role, fn (Builder $query, string $role) => $query->where('role', $role))
            ->orderBy('id');
        foreach ($users->cursor() as $user) {
            $user->notify(new SchoolEventNotification(
                'announcement.created',
                'Pengumuman baru: '.$announcement->title,
                'Ada pengumuman baru dari sekolah.',
                ['announcement_id' => $announcement->id],
                route('public.announcement', $announcement, absolute: false),
            ));
        }
    }

    public function invoice(StudentInvoice $invoice): void
    {
        $invoice->loadMissing('studentProfile.user.parents');
        $student = $invoice->studentProfile->user;
        $recipients = collect([$student])->merge($student->parents)->filter(fn (User $user): bool => $user->is_active)->unique('id');
        foreach ($recipients as $recipient) {
            $url = $recipient->role === User::ROLE_ORANG_TUA
                ? route('orang_tua.payments.index', absolute: false)
                : route('siswa.payments.index', absolute: false);
            $recipient->notify(new SchoolEventNotification(
                'invoice.created',
                'Tagihan baru tersedia',
                'Tagihan '.$invoice->bill_type_name.' sebesar Rp'.number_format($invoice->amount, 0, ',', '.').' telah diterbitkan.',
                ['invoice_id' => $invoice->id],
                $url,
            ));
        }
    }

    public function paymentPaid(StudentInvoice $invoice): void
    {
        $this->notifyInvoiceParties(
            $invoice,
            'payment.paid',
            'Pembayaran lunas',
            'Pembayaran tagihan '.$invoice->bill_type_name.' telah dikonfirmasi lunas.',
        );
    }

    public function ppdbStatusChanged(User $applicant, string $status): void
    {
        $applicant->notify(new SchoolEventNotification(
            'ppdb.status_changed',
            'Status pendaftaran PPDB diperbarui',
            'Status pendaftaran Anda: '.match ($status) {
                'accepted' => 'diterima',
                'rejected' => 'belum dapat diterima',
                default => $status,
            }.'.',
            ['status' => $status],
            route('pendaftar.dashboard', absolute: false),
        ));
    }

    public function ppdbSubmitted(\App\Models\PpdbApplication $application): void
    {
        foreach (User::query()->where('role', User::ROLE_ADMIN)->where('is_active', true)->orderBy('id')->cursor() as $admin) {
            $admin->notify(new SchoolEventNotification(
                'ppdb.status_changed',
                'Pendaftaran PPDB baru menunggu verifikasi',
                'Pendaftaran '.$application->registration_number.' telah dikirim dan menunggu ditinjau.',
                ['application_id' => $application->id],
                route('admin.ppdb.show', $application->id, absolute: false),
            ));
        }
    }

    public function assignment(ClassAssignment $assignment): void
    {
        $assignment->loadMissing(['schoolClass']);
        $students = \App\Models\StudentProfile::query()
            ->with('user.parents')
            ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
            ->where('school_class_id', $assignment->school_class_id)
            ->orderBy('id');

        $students->chunkById(200, function ($profiles) use ($assignment): void {
            foreach ($profiles as $profile) {
                $recipients = collect([$profile->user])->merge($profile->user->parents)->filter(fn (User $user): bool => $user->is_active)->unique('id');
                foreach ($recipients as $recipient) {
                    $recipient->notify(new SchoolEventNotification(
                        'assignment.created',
                        'Tugas baru: '.$assignment->title,
                        'Tugas '.$assignment->title.' harus dikumpulkan paling lambat '.$assignment->due_at->format('d/m/Y H:i').'.',
                        ['assignment_id' => $assignment->id, 'school_class_id' => $assignment->school_class_id],
                        $recipient->role === User::ROLE_ORANG_TUA
                            ? route('orang_tua.children.index', absolute: false)
                            : route('siswa.assignments.index', absolute: false),
                    ));
                }
            }
        });
    }

    public function grade(AssignmentSubmission $submission): void
    {
        $submission->loadMissing('student.user.parents', 'assignment');
        $student = $submission->student->user;
        foreach (collect([$student])->merge($student->parents)->filter(fn (User $user): bool => $user->is_active)->unique('id') as $recipient) {
            $recipient->notify(new SchoolEventNotification(
                'grade.published',
                'Nilai telah diterbitkan',
                'Nilai tugas '.$submission->assignment->title.' sudah dapat dilihat.',
                ['assignment_id' => $submission->assignment_id],
                $recipient->role === User::ROLE_ORANG_TUA
                    ? route('orang_tua.children.assignments', ['student' => $student->id], absolute: false)
                    : route('siswa.assignments.index', absolute: false),
            ));
        }
    }

    public function suspiciousAttendance(TeacherAttendance $attendance): void
    {
        if (! $attendance->is_suspicious) {
            return;
        }

        foreach (User::query()->where('role', User::ROLE_ADMIN)->where('is_active', true)->orderBy('id')->cursor() as $admin) {
            $admin->notify(new SchoolEventNotification(
                'teacher_attendance.suspicious',
                'Absensi guru perlu ditinjau',
                'Ada catatan absensi guru yang ditandai mencurigakan dan memerlukan pemeriksaan.',
                ['teacher_attendance_id' => $attendance->id],
                route('admin.attendance.teachers', absolute: false),
            ));
        }
    }

    private function notifyInvoiceParties(StudentInvoice $invoice, string $event, string $title, string $message): void
    {
        $invoice->loadMissing('studentProfile.user.parents');
        $student = $invoice->studentProfile->user;
        foreach (collect([$student])->merge($student->parents)->filter(fn (User $user): bool => $user->is_active)->unique('id') as $recipient) {
            $recipient->notify(new SchoolEventNotification(
                $event,
                $title,
                $message,
                ['invoice_id' => $invoice->id],
                $recipient->role === User::ROLE_ORANG_TUA
                    ? route('orang_tua.payments.index', absolute: false)
                    : route('siswa.payments.index', absolute: false),
            ));
        }
    }
}
