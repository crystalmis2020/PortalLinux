<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PortalNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Notification $notification)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->notification->to_user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'portal.notification.created';
    }

    public function broadcastWith(): array
    {
        $notification = $this->notification->loadMissing('fromUser');

        return [
            'notification' => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'report_id' => $notification->report_id,
                'details_url' => $notification->report_id
                    ? route('reports.details', ['report' => $notification->report_id, 'notification_id' => $notification->id])
                    : null,
                'created_at' => optional($notification->created_at)?->toIso8601String(),
                'created_at_human' => optional($notification->created_at)?->diffForHumans(),
                'from_user' => $notification->fromUser ? [
                    'id' => $notification->fromUser->id,
                    'full_name' => $notification->fromUser->full_name,
                    'profile_photo_url' => $notification->fromUser->profile_photo_url,
                ] : null,
            ],
            'unread_count' => Notification::where('to_user_id', $notification->to_user_id)
                ->where('is_read', 'No')
                ->count(),
        ];
    }
}
