<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('amount');
            $table->string('school_level', 3)->nullable();
            $table->string('grade', 30)->nullable();
            $table->date('due_date');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['academic_year_id', 'school_level', 'grade']);
        });

        Schema::create('student_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_profile_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('bill_type_name');
            $table->string('billing_period', 20);
            $table->unsignedBigInteger('amount');
            $table->date('due_date');
            $table->string('status', 20)->default('belum_bayar')->index();
            $table->timestamps();
            $table->unique(['bill_type_id', 'student_profile_id', 'billing_period'], 'student_invoice_period_unique');
            $table->index(['student_profile_id', 'status', 'due_date']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_invoice_id')->constrained()->restrictOnDelete();
            $table->string('method', 30);
            $table->string('gateway', 20)->nullable();
            $table->string('gateway_reference')->nullable()->unique();
            $table->string('receipt_number')->nullable()->unique();
            $table->string('status', 20)->index();
            $table->unsignedBigInteger('amount');
            $table->string('payment_url', 2048)->nullable();
            $table->string('proof_path')->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['student_invoice_id', 'status']);
        });

        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20);
            $table->longText('raw_body');
            $table->boolean('signature_valid')->default(false);
            $table->string('processing_status', 30)->default('diterima')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['provider', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('student_invoices');
        Schema::dropIfExists('bill_types');
    }
};
