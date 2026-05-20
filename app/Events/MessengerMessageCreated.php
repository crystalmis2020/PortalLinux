<?php

namespace App\Events;

use App\Models\MessengerMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessengerMessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public MessengerMessage $message)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->message->recipient_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'messenger.message.created';
    }

    public function broadcastWith(): array
    {
        $message = $this->message->loadMissing([
            'sender.department',
            'sender.section',
        ]);

        return [
            'message' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'body' => $message->body,
                'sender_id' => $message->sender_id,
                'recipient_id' => $message->recipient_id,
                'created_at' => optional($message->created_at)?->toIso8601String(),
                'created_at_human' => optional($message->created_at)?->diffForHumans(),
                'time_label' => optional($message->created_at)?->format('M d, Y h:i A'),
                'attachment' => $message->attachment_file_path ? [
                    'original_name' => $message->attachment_original_name,
                    'mime_type' => $message->attachment_mime_type,
                    'size_bytes' => (int) ($message->attachment_size_bytes ?? 0),
                    'view_url' => route('messenger.attachments.view', $message),
                    'download_url' => route('messenger.attachments.download', $message),
                ] : null,
            ],
            'sender' => $message->sender ? [
                'id' => $message->sender->id,
                'full_name' => $message->sender->full_name,
                'username' => $message->sender->username,
                'department' => $message->sender->department?->name,
                'section' => $message->sender->section?->name,
                'avatar_url' => $message->sender->profile_photo_url,
            ] : null,
            'unread_count' => MessengerMessage::where('recipient_id', $message->recipient_id)
                ->whereNull('read_at')
                ->count(),
        ];
    }
}
