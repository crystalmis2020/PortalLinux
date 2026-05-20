<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItemPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'part_name',
        'serial_number',
        'brand',
        'model',
        'remarks',
        'status',
        'installed_at',
        'removed_at',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function historiesAsOldPart(): HasMany
    {
        return $this->hasMany(InventoryPartHistory::class, 'old_part_id');
    }

    public function historiesAsNewPart(): HasMany
    {
        return $this->hasMany(InventoryPartHistory::class, 'new_part_id');
    }
}
