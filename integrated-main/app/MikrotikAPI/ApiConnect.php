<?php

namespace App\mikrotikAPI;

use Routeros_api;

class ApiConnect
{

    protected $routeros_api;

    public function __construct(Routeros_api $routeros_api){
        $this->routeros_api = $routeros_api;
      //  $this->routeros_api->connect('128.0.100.1', 'Alkaloid', 'alkaloid');
    }

    public function connect(){
        $this->routeros_api->connect('128.0.100.1', 'Alkaloid', 'alkaloid');
    }
}