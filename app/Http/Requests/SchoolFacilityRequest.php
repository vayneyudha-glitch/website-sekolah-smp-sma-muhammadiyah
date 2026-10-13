<?php

namespace App\Http\Requests;

use App\Models\SchoolFacility;
use Illuminate\Foundation\Http\FormRequest;

class SchoolFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $facility = $this->route('facility');

        return $facility instanceof SchoolFacility
            ? (bool) $this->user()?->can('update', $facility)
            : (bool) $this->user()?->can('create', SchoolFacility::class);
    }

    protected function prepareForValidation(): void
    {
        foreach (['name', 'description'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim(strip_tags($this->input($field)))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'school_level' => ['nullable', 'in:SMP,SMA'],
        ];
    }
}
