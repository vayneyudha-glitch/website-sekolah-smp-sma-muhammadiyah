<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_import_batches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->longText('payload');
            $table->longText('password_export')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('student_class_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
            $table->unique(['student_profile_id', 'academic_year_id']);
            $table->index(['academic_year_id', 'school_class_id']);
        });

        DB::table('student_profiles')
            ->join('school_classes', 'student_profiles.school_class_id', '=', 'school_classes.id')
            ->select([
                'student_profiles.id as student_profile_id',
                'school_classes.academic_year_id',
                'school_classes.id as school_class_id',
                'student_profiles.status',
                'student_profiles.created_at',
                'student_profiles.updated_at',
            ])
            ->orderBy('student_profiles.id')
            ->chunk(500, function ($students): void {
                DB::table('student_class_histories')->insert(
                    $students->map(fn ($student): array => [
                        'student_profile_id' => $student->student_profile_id,
                        'academic_year_id' => $student->academic_year_id,
                        'school_class_id' => $student->school_class_id,
                        'status' => $student->status,
                        'created_at' => $student->created_at,
                        'updated_at' => $student->updated_at,
                    ])->all(),
                );
            });

        Schema::create('excuse_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->date('attendance_date');
            $table->string('type', 10);
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->string('status', 15)->default('menunggu')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['student_profile_id', 'attendance_date']);
            $table->index(['status', 'attendance_date']);
        });

        Schema::create('grade_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->decimal('weight', 5, 2);
            $table->timestamps();
            $table->unique(['academic_year_id', 'subject_id', 'name']);
            $table->index(['academic_year_id', 'subject_id']);
        });

        Schema::create('grade_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grade_component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2);
            $table->foreignId('entered_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['grade_component_id', 'student_profile_id']);
        });

        Schema::create('grading_bands', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->decimal('minimum_score', 5, 2);
            $table->string('predicate', 20);
            $table->text('description');
            $table->timestamps();
            $table->unique(['academic_year_id', 'minimum_score']);
        });

        Schema::create('report_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_class_id')->constrained()->restrictOnDelete();
            $table->string('status', 15)->default('draf')->index();
            $table->text('homeroom_note')->nullable();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['student_profile_id', 'academic_year_id']);
        });

        Schema::create('report_card_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->decimal('final_score', 5, 2);
            $table->string('predicate', 20);
            $table->text('description');
            $table->timestamps();
            $table->unique(['report_card_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_card_items');
        Schema::dropIfExists('report_cards');
        Schema::dropIfExists('grading_bands');
        Schema::dropIfExists('grade_scores');
        Schema::dropIfExists('grade_components');
        Schema::dropIfExists('excuse_requests');
        Schema::dropIfExists('student_class_histories');
        Schema::dropIfExists('school_import_batches');
    }
};
