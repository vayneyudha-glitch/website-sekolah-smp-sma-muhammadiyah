<?php

namespace App\Providers;

use App\Contracts\WhatsAppSender;
use App\Models\StudentProfile;
use App\Models\StudentInvoice;
use App\Models\SchoolFacility;
use App\Models\SchoolGallery;
use App\Models\SchoolProfileSetting;
use App\Models\Timetable;
use App\Models\User;
use App\Policies\StudentInvoicePolicy;
use App\Policies\SchoolFacilityPolicy;
use App\Policies\SchoolGalleryPolicy;
use App\Policies\SchoolProfileSettingPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use LogicException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WhatsAppSender::class, function (): WhatsAppSender {
            $driver = config('whatsapp.provider', 'log');
            $implementation = config('whatsapp.providers.'.$driver);
            if (! is_string($implementation) || $implementation === '') {
                $implementation = class_exists($driver) && is_subclass_of($driver, WhatsAppSender::class)
                    ? $driver
                    : null;
            }
            if (! is_string($implementation) || $implementation === '') {
                throw new LogicException('Penyedia WhatsApp "'.$driver.'" belum dikonfigurasi.');
            }

            $sender = $this->app->make($implementation);
            if (! $sender instanceof WhatsAppSender) {
                throw new LogicException('Kelas penyedia WhatsApp harus mengimplementasikan WhatsAppSender.');
            }

            return $sender;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(StudentInvoice::class, StudentInvoicePolicy::class);
        Gate::policy(SchoolProfileSetting::class, SchoolProfileSettingPolicy::class);
        Gate::policy(SchoolFacility::class, SchoolFacilityPolicy::class);
        Gate::policy(SchoolGallery::class, SchoolGalleryPolicy::class);

        View::composer(['welcome', 'public.*', 'components.layouts.public'], function ($view): void {
            $profile = Schema::hasTable((new SchoolProfileSetting())->getTable())
                ? SchoolProfileSetting::query()->find(1)
                : null;

            $view->with('schoolProfile', $profile ?? new SchoolProfileSetting());
        });

        Gate::define('view-student-record', function (User $user, StudentProfile $student): bool {
            if ($user->role === User::ROLE_ADMIN) {
                return true;
            }

            if ($user->role === User::ROLE_SISWA) {
                return $student->user_id === $user->id;
            }

            if ($user->role === User::ROLE_ORANG_TUA) {
                return $user->children()->whereKey($student->user_id)->exists();
            }

            return $user->role === User::ROLE_GURU
                && $user->teacherProfile()
                    ->where(function ($query) use ($student): void {
                        $query->whereHas('classes', fn ($classes) => $classes->whereKey($student->school_class_id))
                            ->orWhereHas('homeroomClasses', fn ($classes) => $classes->whereKey($student->school_class_id));
                    })
                    ->exists();
        });

        Gate::define('view-timetable', function (User $user, Timetable $timetable): bool {
            if ($user->role === User::ROLE_ADMIN) {
                return true;
            }

            if ($user->role === User::ROLE_GURU) {
                return $user->teacherProfile()->whereKey($timetable->teacher_profile_id)->exists();
            }

            if ($user->role === User::ROLE_SISWA) {
                return $user->studentProfile()->where('school_class_id', $timetable->school_class_id)->exists();
            }

            return $user->role === User::ROLE_ORANG_TUA
                && $user->children()
                    ->whereHas('studentProfile', fn ($query) => $query->where('school_class_id', $timetable->school_class_id))
                    ->exists();
        });
    }
}
