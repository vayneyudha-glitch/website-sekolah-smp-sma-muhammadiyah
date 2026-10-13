<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return back()->with('status', 'Notifikasi ditandai sudah dibaca.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notification_email_enabled' => ['nullable', 'boolean'],
        ]);

        $request->user()->forceFill([
            'notification_email_enabled' => (bool) ($data['notification_email_enabled'] ?? false),
        ])->save();

        return back()->with('status', 'Preferensi notifikasi berhasil disimpan.');
    }
}
