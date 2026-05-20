<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ItemLog
 *
 * @property int         $sid
 * @property int|null    $po_no
 * @property int|null    $pr_no
 * @property int|null    $line_id
 * @property string|null $item_code
 * @property int|null    $offer_no
 * @property int|null    $vendor_id
 * @property string|null $bp_code
 * @property int|null    $ref_id
 * @property string|null $action
 * @property string|null $table_source
 * @property string|null $table_destination
 * @property \Carbon\CarbonImmutable|null $created
 * @property string|null $sync
 * @property \Carbon\CarbonImmutable|null $sync_date
 */
class ItemLog extends Model
{
    /** Use the external DB */
    protected $connection = 'ops_mysql';

    /** Table & PK */
    protected $table = 'item_log';
    protected $primaryKey = 'sid';
    protected $keyType = 'int';
    public $incrementing = true;

    /**
     * Only a `created` column exists (no updated).
     * Let Eloquent auto-fill it on insert.
     */
    public $timestamps = true;
    public const CREATED_AT = 'created';
    public const UPDATED_AT = null;

    /** Mass-assignable columns */
    protected $fillable = [
        'po_no',
        'pr_no',
        'line_id',
        'item_code',
        'offer_no',
        'vendor_id',
        'bp_code',
        'ref_id',
        'action',
        'table_source',
        'table_destination',
        'sync',
        'sync_date',
        'created', // optional: Eloquent will set this automatically if omitted
    ];

    /**
     * Vendor relationship (ops_mysql)
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'vid');
    }
}
