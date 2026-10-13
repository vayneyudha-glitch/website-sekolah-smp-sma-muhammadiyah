<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SchoolDatasetExport implements FromQuery, WithCustomChunkSize, WithHeadings, WithMapping
{
    /**
     * @param  Builder<Model>  $datasetQuery
     * @param  array<int, string>  $columns
     */
    public function __construct(
        private readonly Builder $datasetQuery,
        private readonly array $columns,
        private readonly string $dataset,
    ) {}

    public function query(): Builder
    {
        return $this->datasetQuery;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function map($row): array
    {
        return array_map(fn (mixed $value): mixed => self::safeCell($value), self::valuesFor($row, $this->dataset));
    }

    /**
     * @return array<int, mixed>
     */
    public static function valuesFor(Model $row, string $dataset): array
    {
        return match ($dataset) {
            'students' => [$row->nis, $row->nisn, $row->user->name, $row->schoolClass->school_level.' '.$row->schoolClass->grade.' '.$row->schoolClass->name, $row->status],
            'teachers' => [$row->employee_number, $row->user->name, $row->user->email],
            'student-attendance', 'attendance' => [$row->attendance_date->format('Y-m-d'), $row->student->nis, $row->student->user->name, $row->student->schoolClass->name, $row->status, $row->checked_in_at?->format('H:i:s')],
            'teacher-attendance' => [$row->attendance_date->format('Y-m-d'), $row->teacher->employee_number, $row->teacher->user->name, $row->check_in_at?->format('H:i:s'), $row->check_out_at?->format('H:i:s'), $row->is_suspicious ? 'Perlu ditinjau' : 'Normal'],
            'grades' => [$row->student->nis, $row->student->user->name, $row->student->schoolClass->name, $row->assignment->subject->name, $row->assignment->title, $row->score, $row->graded_at?->format('Y-m-d H:i:s')],
            'payments' => [$row->invoice->invoice_number, $row->invoice->studentProfile->nis, $row->invoice->studentProfile->user->name, $row->invoice->bill_type_name, $row->amount, $row->status, $row->paid_at?->format('Y-m-d H:i:s')],
            'ppdb' => [$row->registration_number, $row->student_name, $row->school_level, $row->status, $row->submitted_at?->format('Y-m-d H:i:s'), $row->guardian_name, $row->guardian_phone],
            default => [],
        };
    }

    private static function safeCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^[\s]*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
