<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Tandai satu notifikasi dibaca lalu arahkan ke tautan tujuannya.
     * Hanya URL path internal yang diizinkan (anti open-redirect).
     */
    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? '/dashboard';

        return redirect(is_string($url) && str_starts_with($url, '/') ? $url : '/dashboard');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
