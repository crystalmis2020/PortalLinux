<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItemRelease extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'quantity',
        'released_to',
        'department',
        'location',
        'purpose',
        'remarks',
        'released_by',
        'released_at',
    ];

    protected $casts = [
        'released_at' => 'datetime',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}
