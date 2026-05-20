<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MikrotikAPI\Routeros_api;

use App\Mkxpe;

class ProfilesController extends Controller
{
    protected $routeros_api;

    public function __construct(Routeros_api $routeros_api){
        
        $cx = Mkxpe::find(1);

        $this->routeros_api = $routeros_api;
        $this->routeros_api->connect($cx->mkip, $cx->mkau, $cx->mkpw);
   }

   public function index(){

        $profiles =  $this->routeros_api->comm("/tool/user-manager/profile/print");

      //  $profile =  $this->routeros_api->comm("/tool/user-manager/profile/print", array("from" => "*1"));

      // dd($profile);
        
        return view('profiles.index')->with('profiles', $profiles);
   }

   public function limitations(){
    $limitations =  $this->routeros_api->comm("/tool/user-manager/profile/limitation/print");

    return view('profiles.limitations.index')->with('limitations', $limitations);
   }

   public function limitationCreate(){

    $ip_pools =  $this->routeros_api->comm("/ip/pool/print");


    return view('profiles.limitations.create')->with('ip_pools', $ip_pools);
   }

    public function limitationStore(Request $request){
     //   if($request->rate_limit_rx){
     //       print_r($request->rate_limit_rx);
     //   }
     //   dd($request);

        $rate_limit_rx = '';
        $rate_limit_tx = '';
        $rate_limit_burst_rx = '';
        $rate_limit_burst_tx = '';
        $rate_limit_burst_treshold_rx = '';
        $rate_limit_burst_treshold_tx = '';
        $rate_limit_burst_time_rx = '';
        $rate_limit_burst_time_tx = '';
        $rate_limit_min_rx = '';
        $rate_limit_min_tx = '';
        $rate_limit_priority = '';

        $this->validate($request, [
            'name' => 'required'
        ]);

        $profiles =  $this->routeros_api->comm("/tool/user-manager/profile/limitation/print");

        $this->routeros_api->comm("/tool/user-manager/profile/limitation/add", array(
            "owner" => 'xonivre',
            "name" => $request->name
        ));

        // if($request->rate_limit_rx){
        //     $this->validate($request,['rate_limit_rx' => 'int']);
        //     $rate_limit_rx = $request->rate_limit_rx;
        // }

        // if($request->rate_limit_tx){
        //     $this->validate($request,['rate_limit_tx' => 'int']);
        //     $rate_limit_tx = $request->rate_limit_tx;
        // }

        // if($request->rate_limit_burst_rx){
        //     $this->validate($request,['rate_limit_burst_rx' => 'int']);
        //     $rate_limit_burst_rx = $request->rate_limit_burst_rx;
        // }

        // if($request->rate_limit_burst_tx){
        //     $this->validate($request,['rate_limit_burst_tx' => 'int']);
        //     $rate_limit_burst_tx = $request->rate_limit_burst_tx;
        // }

        // if($request->rate_limit_burst_treshold_rx){
        //     $this->validate($request,['rate_limit_burst_treshold_rx' => 'int']);
        //     $rate_limit_burst_treshold_rx = $request->rate_limit_burst_treshold_rx;
        // }

        // if($request->rate_limit_burst_treshold_tx){
        //     $this->validate($request,['rate_limit_burst_treshold_tx' => 'int']);
        //     $rate_limit_burst_treshold_tx = $request->rate_limit_burst_treshold_tx;
        // }

        // if($request->rate_limit_burst_time_rx){
        //     $this->validate($request,['rate_limit_burst_time_rx' => 'int']);
        //     $rate_limit_burst_time_rx = $request->rate_limit_burst_time_rx;
        // }

        // if($request->rate_limit_burst_time_tx){
        //     $this->validate($request,['rate_limit_burst_time_tx' => 'int']);
        //     $rate_limit_burst_time_tx = $request->rate_limit_burst_time_tx;
        // }

        // if($request->rate_limit_min_rx){
        //     $this->validate($request,['rate_limit_min_rx' => 'int']);
        //     $rate_limit_min_rx = $request->rate_limit_min_rx;
        // }

        // if($request->rate_limit_min_tx){
        //     $this->validate($request,['rate_limit_min_tx' => 'int']);
        //     $rate_limit_min_tx = $request->rate_limit_min_tx;
        // }

        // if($request->rate_limit_priority){
        //     $this->validate($request,['rate_limit_priority' => 'int']);
        //     $rate_limit_priority = $request->rate_limit_priority;
        // }

        $set = $this->routeros_api->comm("/tool/user-manager/profile/limitation/set", array(
             "numbers" => count($profiles),
              "ip-pool" => $request->ip_pool
        //     "rate-limit-rx" => $rate_limit_rx,
        //     "rate-limit-tx" => $rate_limit_tx,
        //     "rate-limit-burst-rx" => $rate_limit_burst_rx,
           // "rate-limit-burst-tx" => $rate_limit_burst_tx,
           // "rate-limit-burst-treshold-rx" => $rate_limit_burst_treshold_rx,
           // "rate-limit-burst-treshold_tx" => $request->rate_limit_burst_treshold_tx,
           // "rate-limit-burst-time-rx" => $rate_limit_burst_time_rx,
           // "rate-limit-burst-time-tx" => $rate_limit_burst_time_tx,
           // "rate-limit-min-rx" => $rate_limit_min_rx,
           // "rate-limit-min-tx" => $rate_limit_min_tx, //equal
        //     "rate-limit-priority" => $rate_limit_priority
       ));

        return redirect('/profiles/limitations')->with('success', 'New Limitation successfully created! ('.$request->name.')');
    }

    public function limitationDestroy($id,$name){
        /*
        |--------------------------------------------------------------------------
        | For Future Development
        |--------------------------------------------------------------------------
        |
        | Check database if limitation exist
        | No data should be remove on the database
        | Mark data as deleted, log user who deleted the username (user login details, user IP login into)
        |
        */
       
        $test = $this->routeros_api->comm("/tool/user-manager/profile/limitation/remove", array(".id" => $id));
        return redirect(route('limitationsView'))->with('success', 'Limitation successfully deleted! ('.$name.')');
    }

    public function limitationEdit($id){
        $limitation =  $this->routeros_api->comm("/tool/user-manager/profile/limitation/print", array("from" => $id));
        $ip_pools =  $this->routeros_api->comm("/ip/pool/print");

        return view('profiles.limitations.edit')->with(array('limitation'=>$limitation,'ip_pools'=>$ip_pools));
    }

    public function limitationUpdate(Request $request){

        /*
        |--------------------------------------------------------------------------
        | For Future Development
        |--------------------------------------------------------------------------
        |
        | log user who updated with old value (user login details, user IP login into)
        |
        */

        $this->routeros_api->comm("/tool/user-manager/profile/limitation/set", array(
            ".id" => $request->id,
            "owner" => 'xonivre',
            "name" => $request->name,
            "ip-pool" => $request->ip_pool
        ));

        return redirect('/profiles/limitations/edit/'.$request->id)->with('success', 'Limitation successfully updated! ('.$request->name.')');

    }


}
