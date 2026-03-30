<?php

namespace App\Http\Controllers\Broker;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(): View
    {
        $broker = auth()->user();

        return view('broker.notifications.index', [
            'notifications' => $broker->notifications()->latest()->paginate(20),
            'unreadNotificationsCount' => $broker->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(DatabaseNotification $notification): RedirectResponse
    {
        $broker = auth()->user();

        abort_unless(
            $notification->notifiable_id === $broker->id
                && $notification->notifiable_type === $broker::class,
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
