<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_REPLACED = 'replaced';

    protected $fillable = [
        'item_code',
        'item_name',
        'stock_quantity',
        'assigned_to',
        'department',
        'location',
        'remarks',
        'status',
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_DAMAGED,
            self::STATUS_REPLACED,
        ];
    }

    public function parts(): HasMany
    {
        return $this->hasMany(InventoryItemPart::class)->latest('installed_at')->latest('id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(InventoryPartHistory::class)->latest('action_date')->latest('id');
    }

    public function releases(): HasMany
    {
        return $this->hasMany(InventoryItemRelease::class)->latest('released_at')->latest('id');
    }
}
