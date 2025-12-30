<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display a listing of the user's notifications.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, Notification $notification): RedirectResponse
    {
        // Ensure the notification belongs to the current user
        if ($notification->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read for the current user.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}
