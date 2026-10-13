<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('notification_email_enabled')->default(true);
        });

        Schema::table('ppdb_applications', function (Blueprint $table): void {
            $table->timestamp('privacy_consent_at')->nullable();
            $table->string('privacy_policy_version', 20)->nullable();
        });

        Schema::table('student_attendances', function (Blueprint $table): void {
            $table->timestamp('location_consent_at')->nullable();
        });

        Schema::table('teacher_attendances', function (Blueprint $table): void {
            $table->timestamp('check_in_location_consent_at')->nullable();
            $table->timestamp('check_out_location_consent_at')->nullable();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        Schema::table('teacher_attendances', function (Blueprint $table): void {
            $table->dropColumn(['check_in_location_consent_at', 'check_out_location_consent_at']);
        });

        Schema::table('student_attendances', function (Blueprint $table): void {
            $table->dropColumn('location_consent_at');
        });

        Schema::table('ppdb_applications', function (Blueprint $table): void {
            $table->dropColumn(['privacy_consent_at', 'privacy_policy_version']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('notification_email_enabled');
        });
    }
};
