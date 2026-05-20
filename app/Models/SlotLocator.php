<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlotLocator extends Model
{
    use HasFactory;

    protected $fillable = [
        'coordinates',
        'items',
        'added_by',
    ];

    /**
     * User that added the slot.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
