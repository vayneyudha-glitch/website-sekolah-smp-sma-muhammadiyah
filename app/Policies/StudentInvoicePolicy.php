<?php

namespace App\Policies;

use App\Models\StudentInvoice;
use App\Models\User;

class StudentInvoicePolicy
{
    public function view(User $user, StudentInvoice $invoice): bool
    {
        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        $studentUserId = $invoice->studentProfile()->value('user_id');

        if ($user->role === User::ROLE_SISWA) {
            return $studentUserId !== null && (int) $studentUserId === $user->id;
        }

        return $user->role === User::ROLE_ORANG_TUA
            && $user->children()->whereKey($studentUserId)->exists();
    }

    public function pay(User $user, StudentInvoice $invoice): bool
    {
        return in_array($user->role, [User::ROLE_SISWA, User::ROLE_ORANG_TUA], true)
            && in_array($invoice->status, [StudentInvoice::STATUS_UNPAID, StudentInvoice::STATUS_EXPIRED], true)
            && $this->view($user, $invoice);
    }
}
