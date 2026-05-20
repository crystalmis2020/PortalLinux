<?php

namespace App\Models;


use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


use App\Helpers\LogHelper;


class NetworkHost extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address', 'server_name', 'description', 'host_category_id', 'status', 'last_check', 'added_by',
    ];

    protected $casts = [
        'last_check' => 'datetime',
    ];

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function scopeOnline($q)
    {
        return $q->where('status', 'online');
    }

    public function hostCategory() {
        return $this->belongsTo(HostCategory::class, 'host_category_id');
    }


    /**
     * Boot method for logging report creation.
    */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($report) {
            $actor = Auth::user()?->full_name ?? 'system';
            LogHelper::log('IP Created', $report, 'A new IP has been submitted by: ' . $actor);
        });

        static::updated(function ($report) {
            $actor = Auth::user()?->full_name ?? 'system';
            LogHelper::log('IP Updated', $report, 'An IP has been modified by: '. $actor);
        });

        static::deleted(function ($report) {
            $actor = Auth::user()?->full_name ?? 'system';
            LogHelper::log('IP Deleted', $report, 'An IP has been removed by: '. $actor);
        });
    }

}
