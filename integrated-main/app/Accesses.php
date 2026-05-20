<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Accesses extends Model
{
    //

    public function requests(){
        return $this->belongsTo('App\Requests');
    }
}
