<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LogHelper;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'department_id',
        'section_id',
        'ip_address',
        'username',
        'profile_photo_path',
        'password',
        'user_type',
        'is_active',
        'is_sudo',
        'is_login',
        'last_login',
        'last_seen_at',
        'messenger_presence_visible',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_login' => 'datetime',
            'last_seen_at' => 'datetime',
            'messenger_presence_visible' => 'boolean',
        ];
    }

    protected function isLogin(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => self::legacyFlagToBool($value),
            set: fn (mixed $value) => self::boolToYesNoFlag($value),
        );
    }

    protected function isSudo(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => self::legacyFlagToBool($value),
            set: fn (mixed $value) => self::boolToYesNoFlag($value),
        );
    }

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => self::legacyFlagToBool($value),
            set: fn (mixed $value) => self::boolToOneZeroFlag($value),
        );
    }

    public function getAuthIdentifierName()
    {
        return 'username'; // Use username instead of email
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        if (!empty($this->profile_photo_path)) {
            return asset($this->profile_photo_path);
        }

        return asset('assets/images/avatars/avatar-1.png');
    }

    /**
     * Get the department associated with the user.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get the section associated with the user.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /**
     * Get the sections associated with the messhalls.
     */
    public function messhalls(): HasMany
    {
        return $this->hasMany(Messhall::class);
    }

    public function reportLogs(): HasMany
    {
        return $this->hasMany(ReportLog::class, 'user_id');
    }

    public function sentMessengerMessages(): HasMany
    {
        return $this->hasMany(MessengerMessage::class, 'sender_id');
    }

    public function receivedMessengerMessages(): HasMany
    {
        return $this->hasMany(MessengerMessage::class, 'recipient_id');
    }

    public function isAdmin(): bool
    {
        return strtolower((string) $this->user_type) === 'admin';
    }

    public function isMisMember(): bool
    {
        $sectionCode = strtoupper(trim((string) $this->section?->code));
        $sectionName = strtoupper(trim((string) $this->section?->name));

        return $sectionCode === 'MIS' || $sectionName === 'MIS';
    }

    public function canManageInventory(): bool
    {
        return $this->isAdmin() || $this->isMisMember() || $this->is_sudo;
    }

    public function isCurrentlyOnline(int $minutes = 2): bool
    {
        if ($this->isAdmin() && $this->messenger_presence_visible === false) {
            return false;
        }

        if (!$this->is_login || !$this->last_seen_at) {
            return false;
        }

        return $this->last_seen_at->gte(now()->subMinutes($minutes));
    }

    protected static function legacyFlagToBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'yes', 'true'], true);
    }

    protected static function boolToYesNoFlag(mixed $value): string
    {
        return self::legacyFlagToBool($value) ? 'Yes' : 'No';
    }

    protected static function boolToOneZeroFlag(mixed $value): string
    {
        return self::legacyFlagToBool($value) ? '1' : '0';
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            $actor = Auth::user()?->full_name ?? 'system';
            LogHelper::log('User Created', $user, "New user {$user->full_name} added by {$actor}");
        });

        static::updated(function ($user) {
            $actor = Auth::user()?->full_name ?? 'system';
            LogHelper::log('User Updated', $user, "User {$user->full_name} updated by {$actor}");
        });

        // static::deleted(function ($user) {
        //     LogHelper::log('User Deleted', $user, "User {$user->name} deleted by " . Auth::user()->name);
        // });
    }
}
