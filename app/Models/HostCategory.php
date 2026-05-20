<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Helpers\LogHelper;

class HostCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'added_by'];

    public function addedBy() {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function hosts() {
        return $this->hasMany(NetworkHost::class, 'host_category_id');
    }

    /**
     * Boot method for logging report creation.
    */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($hostCategory) {
            $actor = Auth::user()?->full_name ?? 'system';
            LogHelper::log('Host Category Created', $hostCategory, 'A new Host Category has been submitted by: ' . $actor);
        });

        static::updated(function ($hostCategory) {
            $actor = Auth::user()?->full_name ?? 'system';
            LogHelper::log('Host Category Updated', $hostCategory, 'An Host Category has been modified by: '. $actor);
        });

        static::deleted(function ($hostCategory) {
            $actor = Auth::user()?->full_name ?? 'system';
            LogHelper::log('Host Category Deleted', $hostCategory, 'An Host Category has been removed by: '. $actor);
        });
    }
}
