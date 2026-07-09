<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessengerMessagesRead implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $readerId,
        public int $senderId,
        public array $messageIds,
        public string $readAt,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->senderId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'messenger.messages.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'reader_id' => $this->readerId,
            'sender_id' => $this->senderId,
            'message_ids' => $this->messageIds,
            'read_at' => $this->readAt,
        ];
    }
}
