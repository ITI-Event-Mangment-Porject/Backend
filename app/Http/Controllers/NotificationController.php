<?php

namespace App\Http\Controllers;

use App\Models\NotificationsAndMessaging\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     * GET /notifications
     */
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($notifications);
    }

    /**
     * Mark a notification as read
     * PUT /notifications/{id}/read
     */
    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification
        ]);
    }

    /**
     * Delete a notification
     * DELETE /notifications/{id}
     */
    public function destroy($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Mark all notifications as read
     * POST /notifications/mark-all-read
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Admin sends a notification to any user
     * POST /notifications/admin-send
     */
    public function storeByAdmin(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string',
            'related_id' => 'nullable|integer',
            'related_type' => 'nullable|string',
        ]);

        $notification = Notification::create([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'related_type' => $data['related_type'] ?? null,
            'is_read' => false,
            'sent_via' => json_encode(['in-app']),
        ]);

        return response()->json([
            'message' => 'Notification sent successfully by admin.',
            'notification' => $notification,
        ], 201);
    }


    public function allNotifications(Request $request)
{
  
    $notifications = Notification::with('user') 
        ->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 15));

    return response()->json($notifications);
}
}
