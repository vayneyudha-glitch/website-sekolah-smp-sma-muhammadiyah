<?php

namespace App\Http\Requests;

use App\Models\Payment;
use App\Models\StudentInvoice;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class PaymentReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_ADMIN;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:'.implode(',', [Payment::STATUS_PENDING, Payment::STATUS_PAID, Payment::STATUS_FAILED, Payment::STATUS_EXPIRED, Payment::STATUS_CANCELLED])],
            'school_class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'school_level' => ['nullable', 'in:SMP,SMA'],
            'month' => ['nullable', 'date_format:Y-m'],
            'invoice_status' => ['nullable', 'in:'.implode(',', StudentInvoice::STATUSES)],
        ];
    }
}
