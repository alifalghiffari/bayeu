<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function notifications() {
        $notifications = auth()->user()->notifications;

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    public function notificationsUnread() {
        $unreadNotifications = auth()->user()->unreadNotifications;

        return response()->json([
            'success' => true,
            'data' => $unreadNotifications
        ]);
    }

    public function notificationsRead(Request $request) {
        if ($request->has('id')) {
            $notification = auth()->user()->notifications()->find($request->id);
            if ($notification) {
                $notification->markAsRead();
                return response()->json(['success' => true, 'message' => 'Notification marked as read']);
            }
            return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
        } else {
            auth()->user()->unreadNotifications->markAsRead();
            return response()->json(['success' => true, 'message' => 'All notifications marked as read']);
        }
    }    
}
