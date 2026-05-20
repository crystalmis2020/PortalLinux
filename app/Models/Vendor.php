<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $connection = 'ops_mysql';
    protected $table = 'vendor';
    protected $primaryKey = 'vid';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // set true if your table has timestamps

    protected $fillable = [
        'bp_code',
        'business_name',
        'business_profile',
        'business_address',
        'business_street',
        'business_block',
        'business_city',
        'business_zip',
        'business_state',
        'business_block',
        'business_country',
        'business_tin',
        'business_tel1',
        'business_tel2',
        'business_mobile',
        'business_fax',
        'business_email',
        'business_website',
        'business_logo',
        'business_permit',
        'contact_name',
        'contact_title',
        'contact_position',
        'contact_address',
        'contact_tel1',
        'contact_tel2',
        'contact_mobile',
        'contact_fax',
        'contact_email',
        'contact_residential',
        'email_confirmation',
        'approved',
        'watchlist',
        'created',
    ];

    public function categories()
    {
        // Pivot has extra column: bp_code
        return $this->belongsToMany(Category::class, 'vendor_category', 'vendor_id', 'cat_id')
            ->withPivot('bp_code');
    }


    public function access()
    {
        return $this->hasOne(UserAccess::class, 'vid', 'vid');
    }
}
