<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreBillTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_ADMIN;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'school_level' => ['nullable', 'in:SMP,SMA'],
            'grade' => ['nullable', 'string', 'max:30'],
            'due_date' => ['required', 'date'],
        ];
    }
}
