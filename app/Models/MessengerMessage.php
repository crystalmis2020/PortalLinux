<?php

namespace App\Models;

use App\Events\MessengerMessageCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessengerMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'recipient_id',
        'body',
        'attachment_original_name',
        'attachment_file_path',
        'attachment_mime_type',
        'attachment_size_bytes',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'attachment_size_bytes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::created(function (MessengerMessage $message) {
            if ($message->recipient_id) {
                try {
                    event(new MessengerMessageCreated($message));
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(MessengerConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
