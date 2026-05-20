<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Requests extends Model
{
    //

    public function accesses(){
        return $this->belongsTo('App\Accesses');
    }
}
