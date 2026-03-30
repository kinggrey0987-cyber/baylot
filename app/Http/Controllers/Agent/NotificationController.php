<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(): View
    {
        $agent = auth()->user();

        return view('agent.notifications.index', [
            'notifications' => $agent->notifications()->latest()->paginate(20),
            'unreadNotificationsCount' => $agent->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(DatabaseNotification $notification): RedirectResponse
    {
        $agent = auth()->user();

        abort_unless(
            $notification->notifiable_id === $agent->id
                && $notification->notifiable_type === $agent::class,
            403
        );

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
