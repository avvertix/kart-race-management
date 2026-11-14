<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\NotificationItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The User's notifications kept in the app.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $this->user($request)->notifications()->paginate(20);

        return view('notification.index', [
            'notifications' => $notifications,
            'items' => NotificationItem::forNotifications($notifications->items()),
        ]);
    }

    /**
     * Mark the notification as read and go to what it is about, or to the inbox when either no longer exists, e.g. from
     * the link in an old email once the read notification was deleted.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $this->user($request)->notifications()->find($notification);

        if ($notification === null) {
            return redirect()->route('notifications.index');
        }

        $notification->markAsRead();

        $url = NotificationItem::forNotifications([$notification])->sole()->url;

        return $url === null ? redirect()->route('notifications.index') : redirect()->to($url);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->user($request)->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route('notifications.index');
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
