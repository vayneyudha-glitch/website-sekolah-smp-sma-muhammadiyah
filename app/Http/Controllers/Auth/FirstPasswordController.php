<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class FirstPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.first-password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $request->user()->forceFill([
                'password' => Hash::make($validated['password']),
                'must_change_password' => false,
            ])->save();

            AuditLog::query()->create([
                'user_id' => $request->user()->id,
                'action' => 'password_changed',
                'record_type' => 'accounts',
                'record_id' => $request->user()->id,
                'description' => 'Mengganti kata sandi sementara',
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()->route($request->user()->dashboardRouteName())
            ->with('status', 'Kata sandi Anda berhasil diperbarui.');
    }
}
