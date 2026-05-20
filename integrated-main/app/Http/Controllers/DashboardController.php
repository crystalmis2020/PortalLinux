<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MikrotikAPI\Routeros_api;

class DashboardController extends Controller
{
    //

    protected $routeros_api;

    public function __construct(Routeros_api $routeros_api){
       $this->routeros_api = $routeros_api;
       $this->routeros_api->connect('128.0.100.1', 'Alkaloid', 'alkaloid');
   }

    public function index(){

        return view('index');
    }

}
