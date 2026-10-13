<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table): void {
            $table->tinyIncrements('id');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('radius_meters')->default(100);
            $table->time('student_check_in_starts_at')->default('06:00:00');
            $table->time('student_check_in_ends_at')->default('08:00:00');
            $table->time('teacher_check_in_starts_at')->default('06:00:00');
            $table->time('teacher_check_in_ends_at')->default('08:00:00');
            $table->timestamps();
        });

        Schema::create('student_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('status', 20);
            $table->timestamp('checked_in_at')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->decimal('distance_meters', 10, 2)->nullable();
            $table->text('reason')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('edited_at')->nullable();
            $table->text('correction_reason')->nullable();
            $table->timestamps();
            $table->unique(['student_profile_id', 'attendance_date']);
            $table->index(['attendance_date', 'status']);
        });

        Schema::create('teacher_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->timestamp('check_in_at')->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_in_accuracy_meters', 8, 2)->nullable();
            $table->string('check_in_ip_address', 45)->nullable();
            $table->string('check_in_device_hash', 64)->nullable();
            $table->string('check_in_selfie_path')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->decimal('check_out_accuracy_meters', 8, 2)->nullable();
            $table->string('check_out_ip_address', 45)->nullable();
            $table->string('check_out_device_hash', 64)->nullable();
            $table->string('check_out_selfie_path')->nullable();
            $table->boolean('is_suspicious')->default(false)->index();
            $table->json('suspicious_reasons')->nullable();
            $table->timestamps();
            $table->unique(['teacher_profile_id', 'attendance_date']);
            $table->index(['attendance_date', 'teacher_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_attendances');
        Schema::dropIfExists('student_attendances');
        Schema::dropIfExists('attendance_settings');
    }
};
