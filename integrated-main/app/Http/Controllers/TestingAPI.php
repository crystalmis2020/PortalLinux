<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MikrotikAPI\Routeros_api;

class TestingAPI extends Controller
{
    //

   protected $routeros_api;

    public function __construct(Routeros_api $routeros_api){
       $this->routeros_api = $routeros_api;
       $this->routeros_api->connect('128.0.100.1', 'Alkaloid', 'alkaloid');
   }

    public function test(){


        // $API->connect($ip, $user, $pass)
        // $users = $API->comm("/tool/user-manager/user/print");

        
        $users =  $this->routeros_api->comm("/tool/user-manager/user/print");

        dd($users);

        $API->disconnect();

        return "test";
    }
}
