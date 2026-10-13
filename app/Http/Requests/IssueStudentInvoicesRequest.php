<?php

namespace App\Http\Requests;

use App\Models\BillType;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class IssueStudentInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_ADMIN;
    }

    public function rules(): array
    {
        return [
            'bill_type_id' => ['required', 'integer', 'exists:bill_types,id'],
            'school_class_id' => ['nullable', 'required_without:school_level', 'integer', 'exists:school_classes,id'],
            'school_level' => ['nullable', 'required_without:school_class_id', 'in:SMP,SMA'],
            'grade' => ['nullable', 'string', 'max:30'],
            'billing_period' => ['required', 'date_format:Y-m'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = BillType::query()->with('academicYear')->find($this->input('bill_type_id'));
            if ($type === null) {
                return;
            }

            if ($type->is_active !== true) {
                $validator->errors()->add('bill_type_id', 'Jenis tagihan ini sudah tidak aktif.');
            }

            if ($this->filled('school_class_id')) {
                $schoolClass = SchoolClass::query()->find($this->input('school_class_id'));
                if ($schoolClass !== null && (
                    (int) $schoolClass->academic_year_id !== (int) $type->academic_year_id
                    || ($type->school_level !== null && $schoolClass->school_level !== $type->school_level)
                    || ($type->grade !== null && $schoolClass->grade !== $type->grade)
                )) {
                    $validator->errors()->add('school_class_id', 'Kelas tidak sesuai sasaran jenis tagihan.');
                }
            } elseif ($this->filled('school_level')) {
                if (
                    ($type->school_level !== null && $this->input('school_level') !== $type->school_level)
                    || ($type->grade !== null && $this->input('grade') !== $type->grade)
                ) {
                    $validator->errors()->add('school_level', 'Jenjang atau kelas tidak sesuai sasaran jenis tagihan.');
                }
            }
        });
    }
}
