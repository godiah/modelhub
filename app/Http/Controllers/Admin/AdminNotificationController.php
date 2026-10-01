<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\NotificationPresenterHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** Staff notifications: new models, applications and disputes waiting for them. */
class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(15)->through(fn ($notification) => [
            'id' => $notification->id,
            'read' => $notification->read_at !== null,
            'at' => $notification->created_at,
        ] + NotificationPresenterHelper::present($notification));

        return view('admin.notifications.index', ['notifications' => $notifications, 'unread' => $request->user()->unreadNotifications()->count()]);
    }

    /** Opening a notification marks it read and goes where it points. */
    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect()->to(NotificationPresenterHelper::present($notification)['action_url'] ?? route('admin.notifications.index'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
