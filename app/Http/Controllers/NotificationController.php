<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'notification_ids' => ['required', 'array', 'min:1'],
            'notification_ids.*' => ['integer'],
            'is_read' => ['required', 'in:Yes,No'],
        ]);

        $updated = Notification::where('to_user_id', $request->user()->id)
            ->whereIn('id', $validated['notification_ids'])
            ->update([
                'is_read' => $validated['is_read'],
                'updated_at' => now(),
            ]);

        $unreadCount = Notification::where('to_user_id', $request->user()->id)
            ->where('is_read', 'No')
            ->count();

        return response()->json([
            'updated' => $updated,
            'unread_count' => $unreadCount,
        ]);
    }
}
