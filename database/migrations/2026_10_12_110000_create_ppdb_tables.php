<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('previous_school')->nullable();
        });

        Schema::create('ppdb_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_open')->default(false);
            $table->text('wave_schedule')->nullable();
            $table->text('registration_flow')->nullable();
            $table->text('costs')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
        });

        DB::table('ppdb_settings')->insert([
            'id' => 1,
            'is_open' => false,
            'wave_schedule' => 'Informasi jadwal PPDB belum diatur.',
            'registration_flow' => 'Informasi alur pendaftaran belum diatur.',
            'costs' => 'Informasi biaya belum diatur.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('ppdb_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('registration_number', 40)->nullable()->unique();
            $table->string('student_name');
            $table->string('nisn', 10)->nullable()->unique();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('previous_school')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_relation', 50)->nullable();
            $table->string('guardian_phone', 30)->nullable();
            $table->string('guardian_email')->nullable();
            $table->string('school_level', 3)->nullable()->index();
            $table->string('major')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->text('decision_reason')->nullable();
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['school_level', 'status']);
        });

        Schema::create('ppdb_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ppdb_application_id')->constrained('ppdb_applications')->cascadeOnDelete();
            $table->string('category', 40);
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->string('status', 20)->default('waiting')->index();
            $table->text('review_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['ppdb_application_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppdb_documents');
        Schema::dropIfExists('ppdb_applications');
        Schema::dropIfExists('ppdb_settings');

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['birth_place', 'birth_date', 'address', 'previous_school']);
        });
    }
};
