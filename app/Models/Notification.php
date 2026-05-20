<?php

namespace App\Models;

use App\Events\PortalNotificationCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'section_to',
        'report_id',
        'title',
        'message',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'string', // Ensure ENUM is treated as a string
    ];

    protected static function booted(): void
    {
        static::created(function (Notification $notification) {
            if ($notification->to_user_id) {
                try {
                    event(new PortalNotificationCreated($notification));
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
        });
    }

    public function fromUser(){
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(){
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function section(){
        return $this->belongsTo(Section::class, 'section_to');
    }

    public function report(){
        return $this->belongsTo(Report::class, 'report_id');
    }
}
