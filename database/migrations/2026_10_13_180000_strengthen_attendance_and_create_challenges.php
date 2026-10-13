<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table): void {
            $table->unsignedInteger('max_accuracy_meters')->default(100);
        });

        Schema::table('student_attendances', function (Blueprint $table): void {
            $table->string('device_hash', 64)->nullable()->index();
            $table->index(['latitude', 'longitude']);
            $table->boolean('needs_review')->default(false)->index();
            $table->string('review_status', 24)->default('bersih')->index();
            $table->json('suspicious_reasons')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
        });

        Schema::table('teacher_attendances', function (Blueprint $table): void {
            $table->index(['check_in_latitude', 'check_in_longitude']);
            $table->index(['check_out_latitude', 'check_out_longitude']);
            $table->string('review_status', 24)->default('bersih')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
        });
        DB::table('teacher_attendances')
            ->where('is_suspicious', true)
            ->update(['review_status' => 'perlu_ditinjau']);

        Schema::create('attendance_challenges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 128);
            $table->string('attendance_type', 32);
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->index(['user_id', 'attendance_type', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_challenges');

        Schema::table('teacher_attendances', function (Blueprint $table): void {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['check_in_latitude', 'check_in_longitude']);
            $table->dropIndex(['check_out_latitude', 'check_out_longitude']);
            $table->dropIndex(['review_status']);
            $table->dropColumn(['review_status', 'reviewed_by', 'reviewed_at', 'review_note']);
        });

        Schema::table('student_attendances', function (Blueprint $table): void {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['device_hash']);
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['needs_review']);
            $table->dropIndex(['review_status']);
            $table->dropColumn(['device_hash', 'needs_review', 'review_status', 'suspicious_reasons', 'reviewed_by', 'reviewed_at', 'review_note']);
        });

        Schema::table('attendance_settings', function (Blueprint $table): void {
            $table->dropColumn('max_accuracy_meters');
        });
    }
};
