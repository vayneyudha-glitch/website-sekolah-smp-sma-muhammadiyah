<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('grade');
            $table->string('school_level', 3);
            $table->timestamps();
            $table->unique(['academic_year_id', 'name']);
            $table->index(['school_level', 'grade']);
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('student_number')->unique();
            $table->foreignId('school_class_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->index(['school_class_id', 'student_number']);
        });

        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_number')->unique();
            $table->text('bio')->nullable();
            $table->timestamps();
        });

        Schema::create('class_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['school_class_id', 'teacher_profile_id']);
            $table->index(['teacher_profile_id', 'school_class_id']);
        });

        Schema::create('parent_child', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['parent_id', 'student_id']);
            $table->index(['student_id', 'parent_id']);
        });

        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_class_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_profile_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('location')->nullable();
            $table->timestamps();
            $table->index(['academic_year_id', 'school_class_id', 'day_of_week']);
            $table->index(['teacher_profile_id', 'day_of_week']);
            $table->unique(['school_class_id', 'day_of_week', 'starts_at']);
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('timetables');
        Schema::dropIfExists('parent_child');
        Schema::dropIfExists('class_teacher');
        Schema::dropIfExists('teacher_profiles');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('academic_years');
    }
};
