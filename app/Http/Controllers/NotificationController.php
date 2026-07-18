<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = Notification::forOwner(auth('owner')->id())
            ->latest()
            ->paginate(20);

        $unreadCount = Notification::forOwner(auth('owner')->id())->unread()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /** Mark a single notification read and redirect to its target (bell click-through). */
    public function open(int $id): RedirectResponse
    {
        $notification = $this->findOwned($id);
        $notification->markAsRead();

        return redirect($notification->action_url ?: '/notifications');
    }

    public function markRead(int $id): RedirectResponse
    {
        $this->findOwned($id)->markAsRead();

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        Notification::forOwner(auth('owner')->id())
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('success', __('app.notif.all_marked_read'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->findOwned($id)->delete();

        return back()->with('success', __('app.notif.deleted'));
    }

    private function findOwned(int $id): Notification
    {
        return Notification::forOwner(auth('owner')->id())->findOrFail($id);
    }
}
