<?php

namespace App\Http\Requests;

use App\Models\SchoolProfileSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $setting = SchoolProfileSetting::query()->findOrFail(1);

        return $this->user()?->can('update', $setting) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $input = $this->all();
        foreach ([
            'name', 'tagline', 'address', 'phone', 'whatsapp', 'email', 'latitude',
            'longitude', 'map_link', 'service_hours', 'welcome_message', 'vision',
            'mission', 'history', 'smp_description', 'sma_description',
        ] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = trim(strip_tags($input[$field]));
            }
        }

        if (isset($input['social_links']) && is_array($input['social_links'])) {
            foreach ($input['social_links'] as $key => $value) {
                if (is_string($value)) {
                    $input['social_links'][$key] = trim(strip_tags($value));
                }
            }
        }

        $this->replace($input);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:2000'],
            'phone' => ['required', 'string', 'max:40'],
            'whatsapp' => [
                'required',
                'string',
                'max:40',
                Rule::when($this->input('whatsapp') !== '[Isi dari panel admin]', ['regex:/^\+?[0-9][0-9\s().-]{6,38}$/']),
            ],
            'email' => [
                'required',
                'string',
                'max:255',
                Rule::when($this->input('email') !== '[Isi dari panel admin]', ['email']),
            ],
            'latitude' => [
                'required',
                'string',
                'max:40',
                Rule::when($this->input('latitude') !== '[Isi dari panel admin]', ['numeric', 'between:-90,90']),
            ],
            'longitude' => [
                'required',
                'string',
                'max:40',
                Rule::when($this->input('longitude') !== '[Isi dari panel admin]', ['numeric', 'between:-180,180']),
            ],
            'map_link' => [
                'required',
                'string',
                'max:2048',
                Rule::when($this->input('map_link') !== '[Isi dari panel admin]', ['url']),
            ],
            'service_hours' => ['required', 'string', 'max:255'],
            'welcome_message' => ['required', 'string', 'max:20000'],
            'vision' => ['required', 'string', 'max:10000'],
            'mission' => ['required', 'string', 'max:10000'],
            'history' => ['required', 'string', 'max:20000'],
            'smp_description' => ['required', 'string', 'max:10000'],
            'sma_description' => ['required', 'string', 'max:10000'],
            'social_links' => ['required', 'array'],
            'social_links.instagram' => ['required', 'string', 'max:255'],
            'social_links.facebook' => ['required', 'string', 'max:255'],
            'social_links.youtube' => ['required', 'string', 'max:255'],
            'social_links.tiktok' => ['required', 'string', 'max:255'],
        ];
    }
}
