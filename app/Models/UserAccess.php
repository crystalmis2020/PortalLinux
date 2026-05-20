<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccess extends Model
{
    protected $connection = 'ops_mysql';
    protected $table = 'user_access';
    protected $primaryKey = 'uaid';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'vid',       // varchar in table, we will cast the int vendor id to string
        'bp_code',
        'username',
        'password',
        'temp_pass',
        'created',
    ];
}
