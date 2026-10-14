<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addColumnIfMissing('attendance_settings', 'max_accuracy_meters', function (Blueprint $table): void {
            $table->unsignedInteger('max_accuracy_meters')->default(100);
        });

        $this->addColumnIfMissing('student_attendances', 'device_hash', function (Blueprint $table): void {
            $table->string('device_hash', 64)->nullable();
        });
        $this->addColumnIfMissing('student_attendances', 'needs_review', function (Blueprint $table): void {
            $table->boolean('needs_review')->default(false);
        });
        $this->addColumnIfMissing('student_attendances', 'review_status', function (Blueprint $table): void {
            $table->string('review_status', 24)->default('bersih');
        });
        $this->addColumnIfMissing('student_attendances', 'suspicious_reasons', function (Blueprint $table): void {
            $table->json('suspicious_reasons')->nullable();
        });
        $this->addColumnIfMissing('student_attendances', 'reviewed_by', function (Blueprint $table): void {
            $table->foreignId('reviewed_by')->nullable();
        });
        $this->addColumnIfMissing('student_attendances', 'reviewed_at', function (Blueprint $table): void {
            $table->timestamp('reviewed_at')->nullable();
        });
        $this->addColumnIfMissing('student_attendances', 'review_note', function (Blueprint $table): void {
            $table->text('review_note')->nullable();
        });
        $this->addIndexIfMissing('student_attendances', ['device_hash']);
        $this->addIndexIfMissing('student_attendances', ['latitude', 'longitude']);
        $this->addIndexIfMissing('student_attendances', ['needs_review']);
        $this->addIndexIfMissing('student_attendances', ['review_status']);
        $this->addForeignKeyIfMissing('student_attendances', 'reviewed_by', 'users', 'set null');

        $this->addIndexIfMissing('teacher_attendances', ['check_in_latitude', 'check_in_longitude']);
        $this->addIndexIfMissing('teacher_attendances', ['check_out_latitude', 'check_out_longitude']);
        $this->addColumnIfMissing('teacher_attendances', 'review_status', function (Blueprint $table): void {
            $table->string('review_status', 24)->default('bersih');
        });
        $this->addColumnIfMissing('teacher_attendances', 'reviewed_by', function (Blueprint $table): void {
            $table->foreignId('reviewed_by')->nullable();
        });
        $this->addColumnIfMissing('teacher_attendances', 'reviewed_at', function (Blueprint $table): void {
            $table->timestamp('reviewed_at')->nullable();
        });
        $this->addColumnIfMissing('teacher_attendances', 'review_note', function (Blueprint $table): void {
            $table->text('review_note')->nullable();
        });
        $this->addIndexIfMissing('teacher_attendances', ['review_status']);
        $this->addForeignKeyIfMissing('teacher_attendances', 'reviewed_by', 'users', 'set null');

        DB::table('teacher_attendances')
            ->where('is_suspicious', true)
            ->update(['review_status' => 'perlu_ditinjau']);

        if (! Schema::hasTable('attendance_challenges')) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_challenges');

        $this->dropForeignKeyIfExists('teacher_attendances', ['reviewed_by']);
        $this->dropIndexIfExists('teacher_attendances', ['check_in_latitude', 'check_in_longitude']);
        $this->dropIndexIfExists('teacher_attendances', ['check_out_latitude', 'check_out_longitude']);
        $this->dropIndexIfExists('teacher_attendances', ['review_status']);
        $this->dropColumnsIfPresent('teacher_attendances', [
            'review_status',
            'reviewed_by',
            'reviewed_at',
            'review_note',
        ]);

        $this->dropForeignKeyIfExists('student_attendances', ['reviewed_by']);
        $this->dropIndexIfExists('student_attendances', ['device_hash']);
        $this->dropIndexIfExists('student_attendances', ['latitude', 'longitude']);
        $this->dropIndexIfExists('student_attendances', ['needs_review']);
        $this->dropIndexIfExists('student_attendances', ['review_status']);
        $this->dropColumnsIfPresent('student_attendances', [
            'device_hash',
            'needs_review',
            'review_status',
            'suspicious_reasons',
            'reviewed_by',
            'reviewed_at',
            'review_note',
        ]);

        $this->dropColumnsIfPresent('attendance_settings', ['max_accuracy_meters']);
    }

    private function addColumnIfMissing(string $tableName, string $columnName, callable $definition): void
    {
        if (! Schema::hasTable($tableName)) {
            throw new RuntimeException("Required table [{$tableName}] does not exist.");
        }

        if (! Schema::hasColumn($tableName, $columnName)) {
            Schema::table($tableName, $definition);
        }
    }

    private function addIndexIfMissing(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            throw new RuntimeException("Required table [{$tableName}] does not exist.");
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($tableName, $column)) {
                throw new RuntimeException("Required column [{$tableName}.{$column}] does not exist.");
            }
        }

        foreach (Schema::getIndexes($tableName) as $index) {
            if (($index['columns'] ?? []) === $columns) {
                return;
            }
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns): void {
            $table->index($columns);
        });
    }

    private function addForeignKeyIfMissing(
        string $tableName,
        string $column,
        string $referencedTable,
        string $onDelete,
    ): void {
        if (! Schema::hasTable($tableName)) {
            throw new RuntimeException("Required table [{$tableName}] does not exist.");
        }

        if (! Schema::hasColumn($tableName, $column)) {
            throw new RuntimeException("Required column [{$tableName}.{$column}] does not exist.");
        }

        foreach (Schema::getForeignKeys($tableName) as $foreignKey) {
            if (($foreignKey['columns'] ?? []) === [$column]) {
                return;
            }
        }

        Schema::table($tableName, function (Blueprint $table) use ($column, $referencedTable, $onDelete): void {
            $table->foreign($column)
                ->references('id')
                ->on($referencedTable)
                ->onDelete($onDelete);
        });
    }

    private function dropForeignKeyIfExists(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        foreach (Schema::getForeignKeys($tableName) as $foreignKey) {
            if (($foreignKey['columns'] ?? []) === $columns) {
                Schema::table($tableName, function (Blueprint $table) use ($foreignKey): void {
                    $table->dropForeign($foreignKey['name']);
                });

                return;
            }
        }
    }

    private function dropIndexIfExists(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        foreach (Schema::getIndexes($tableName) as $index) {
            if (($index['columns'] ?? []) === $columns && ! ($index['primary'] ?? false)) {
                Schema::table($tableName, function (Blueprint $table) use ($index): void {
                    $table->dropIndex($index['name']);
                });

                return;
            }
        }
    }

    private function dropColumnsIfPresent(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        foreach ($columns as $column) {
            if (Schema::hasColumn($tableName, $column)) {
                Schema::table($tableName, function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
