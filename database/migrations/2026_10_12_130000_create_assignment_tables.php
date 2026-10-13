<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('school_class_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamp('due_at')->index();
            $table->timestamps();
            $table->index(['school_class_id', 'subject_id', 'due_at']);
        });

        Schema::create('assignment_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_id')->constrained('class_assignments')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->longText('body')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('submitted_at');
            $table->boolean('is_late')->default(false);
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
            $table->unique(['assignment_id', 'student_profile_id']);
            $table->index(['assignment_id', 'graded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('class_assignments');
    }
};
