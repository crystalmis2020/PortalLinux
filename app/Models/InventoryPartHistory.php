<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryPartHistory extends Model
{
    use HasFactory;

    public const ACTION_DAMAGED = 'damaged';
    public const ACTION_REPLACED = 'replaced';

    protected $fillable = [
        'inventory_item_id',
        'old_part_id',
        'new_part_id',
        'part_name',
        'action_type',
        'reason',
        'remarks',
        'action_date',
        'performed_by',
    ];

    protected $casts = [
        'action_date' => 'datetime',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function oldPart(): BelongsTo
    {
        return $this->belongsTo(InventoryItemPart::class, 'old_part_id');
    }

    public function newPart(): BelongsTo
    {
        return $this->belongsTo(InventoryItemPart::class, 'new_part_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
