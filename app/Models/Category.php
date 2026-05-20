<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $connection = 'ops_mysql';
    protected $table = 'category';
    protected $primaryKey = 'cid';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = ['ref_id', 'name'];

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'vendor_category', 'cat_id', 'vendor_id')
            ->withPivot('bp_code');
    }
}
