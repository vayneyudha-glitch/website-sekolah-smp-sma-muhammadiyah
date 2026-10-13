<?php

namespace App\Http\Requests;

use App\Models\PaymentWebhookLog;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

class PaymentWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->route('provider'), ['midtrans', 'xendit'], true);
    }

    public function rules(): array
    {
        return [
            'order_id' => ['sometimes', 'string', 'max:255'],
            'status_code' => ['sometimes', 'string', 'max:20'],
            'gross_amount' => ['sometimes', 'numeric'],
            'transaction_status' => ['sometimes', 'string', 'max:40'],
            'signature_key' => ['sometimes', 'string', 'max:255'],
            'external_id' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:40'],
            'amount' => ['sometimes', 'numeric'],
            'paid_amount' => ['sometimes', 'numeric'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->route('provider') === 'midtrans') {
                foreach (['order_id', 'status_code', 'gross_amount', 'transaction_status', 'signature_key'] as $field) {
                    if (! $this->filled($field)) {
                        $validator->errors()->add($field, 'Informasi notifikasi Midtrans tidak lengkap.');
                    }
                }
            }

            if ($this->route('provider') === 'xendit') {
                foreach (['external_id', 'status'] as $field) {
                    if (! $this->filled($field)) {
                        $validator->errors()->add($field, 'Informasi notifikasi Xendit tidak lengkap.');
                    }
                }

                if (! $this->filled('amount') && ! $this->filled('paid_amount')) {
                    $validator->errors()->add('amount', 'Nominal notifikasi Xendit wajib diisi.');
                }
            }
        });
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        PaymentWebhookLog::query()->create([
            'provider' => (string) $this->route('provider'),
            'raw_body' => '[redacted]',
            'signature_valid' => false,
            'processing_status' => 'payload_tidak_valid',
            'error_message' => $validator->errors()->first(),
            'processed_at' => now(),
        ]);

        throw new HttpResponseException(response()->json([
            'message' => 'Format notifikasi tidak valid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}