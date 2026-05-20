<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessengerCallSignalReceived implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $sender,
        public User $recipient,
        public array $signal
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->recipient->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'messenger.call.signal';
    }

    public function broadcastWith(): array
    {
        return [
            'signal' => $this->signal,
            'sender' => [
                'id' => $this->sender->id,
                'full_name' => $this->sender->full_name,
                'username' => $this->sender->username,
                'department' => $this->sender->department?->name,
                'section' => $this->sender->section?->name,
                'avatar_url' => $this->sender->profile_photo_url,
            ],
        ];
    }
}
