<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MikrotikAPI\Routeros_api;
use App\Mkxpe;
use App\Logs;
use App\Accesses;

class Crons extends Controller
{
    protected $routeros_api;

    public function __construct(Routeros_api $routeros_api){
        $this->routeros_api = $routeros_api;
   }

    function usedUptime(){
        $cx = Mkxpe::find(1);
        $this->routeros_api->connect($cx->mkip, $cx->mkau, $cx->mkpw);

        $accessesMT =  $this->routeros_api->comm("/tool/user-manager/user/print");
        $accessesDB = Accesses::where('is_used', 'No')->get();
        
        foreach($accessesDB AS $access):
            $ac = $this->routeros_api->comm("/tool/user-manager/user/print", array('from' => $access->username));
            if($ac[0]['last-seen'] != 'never'):
                $consumed = substr($ac[0]['uptime-used'], 0, 2);
                $yes = ($access->hours == $consumed) ? 'Yes' : 'No';
                
                if($access->hours == $consumed ){ //$access->hours == $consumed 
                    $access = Accesses::find($access->id);
                        
                    $access->is_used = 'Yes';
                    $access->used_uptime = $consumed;
                    $access->save();

                    $log = new Logs;
                    $log->message = 'Cron has updated an access to is_used = Yes';
                    $log->table = 'accesses';
                    $log->table_id = $access->id;
                    $log->user = $_SERVER['REMOTE_ADDR']; // change this to login admin
                    $log->ip = $_SERVER['REMOTE_ADDR'];
                    $log->save();

                    $remove = $this->routeros_api->comm("/tool/user-manager/user/remove", array(".id" => $ac[0]['.id']));
                }else{
                    $access->used_uptime = $ac[0]['uptime-used'];
                    $access->save();

                    $log = new Logs;
                    $log->message = 'Cron has updated an access to used_uptime = '.$ac[0]['uptime-used'];
                    $log->table = 'accesses';
                    $log->table_id = $access->id;
                    $log->user = $_SERVER['REMOTE_ADDR']; // change this to login admin
                    $log->ip = $_SERVER['REMOTE_ADDR'];
                    $log->save();
                }

            endif;

        endforeach;
       
    }

}
