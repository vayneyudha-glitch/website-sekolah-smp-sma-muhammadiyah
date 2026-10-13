<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StartOnlinePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [User::ROLE_SISWA, User::ROLE_ORANG_TUA], true);
    }

    public function rules(): array
    {
        return ['method' => ['required', 'in:virtual_account,qris,e_wallet']];
    }
}
