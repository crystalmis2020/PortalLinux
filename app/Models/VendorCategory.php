<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VendorCategory extends Model
{
    protected $connection = 'ops_mysql';
    protected $table = 'vendor_category';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'bp_code',
        'cat_id',     // will hold category.ref_id (varchar)
        'approve',   // NEW in fillable
        'created',
        'updated',
    ];

    /**
     * Get a vendor's categories via direct INNER JOIN on ops_mysql.
     *
     * @param  int|string  $vendorId
     * @param  bool        $onlyApproved  When true, filters vc.approved = '1'
     * @return \Illuminate\Support\Collection
     */
    public static function categoriesForVendor($vendorId, bool $onlyApproved = false)
    {
        $query = DB::connection('ops_mysql')
            ->table('vendor_category as vc')
            ->join('category as c', 'c.ref_id', '=', 'vc.cat_id') // INNER JOIN
            ->where('vc.vendor_id', $vendorId)
            ->orderBy('c.name');

        // if ($onlyApproved) {
        //     $query->where('vc.approve', '1');
        // }

        return $query->get([
            'vc.id',
            'vc.vendor_id',
            'vc.bp_code',
            'vc.cat_id',                 // stores category.ref_id
            'vc.approve',
            'vc.created',
            'vc.updated',
            'c.name   as category_name',
            'c.ref_id as category_ref_id',
        ]);
    }

}
