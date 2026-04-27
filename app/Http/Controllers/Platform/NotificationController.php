<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = PlatformNotification::query()->with('school:id,name,slug');

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        $notifications = $query
            ->latest('id')
            ->paginate(25)
            ->appends($request->query());

        $unreadCount = PlatformNotification::whereNull('read_at')->count();

        return view('platform.notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markRead(PlatformNotification $notification): RedirectResponse
    {
        $notification->markAsRead();

        return back()->with('status', 'Notification marked as read.');
    }
}
