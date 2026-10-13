<?php

namespace App\Http\Requests;

use App\Models\SchoolGallery;
use Illuminate\Foundation\Http\FormRequest;

class SchoolGalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', SchoolGallery::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('title'))) {
            $this->merge(['title' => trim(strip_tags($this->input('title')))]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'school_level' => ['nullable', 'in:SMP,SMA'],
        ];
    }
}
