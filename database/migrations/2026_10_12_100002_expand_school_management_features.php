<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('must_change_password')->default(false);
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('nis', 50)->nullable()->unique();
            $table->string('nisn', 20)->nullable()->unique();
            $table->string('status', 20)->default('aktif')->index();
        });

        DB::table('student_profiles')->update(['nis' => DB::raw('student_number')]);

        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->string('photo_path')->nullable();
        });

        Schema::table('school_classes', function (Blueprint $table) {
            $table->foreignId('homeroom_teacher_profile_id')
                ->nullable()
                ->constrained('teacher_profiles')
                ->nullOnDelete();
        });

        Schema::create('subject_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['subject_id', 'teacher_profile_id']);
            $table->index(['teacher_profile_id', 'subject_id']);
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->string('category', 80)->default('Pengumuman');
            $table->string('image_path')->nullable();
            $table->string('audience_role')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['category', 'image_path', 'audience_role']);
        });

        Schema::dropIfExists('subject_teacher');

        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('homeroom_teacher_profile_id');
        });

        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropUnique(['nis']);
            $table->dropUnique(['nisn']);
            $table->dropIndex(['status']);
            $table->dropColumn(['nis', 'nisn', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn(['is_active', 'must_change_password']);
        });
    }
};
