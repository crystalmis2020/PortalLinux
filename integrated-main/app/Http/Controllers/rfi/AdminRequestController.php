<?php

namespace App\Http\Controllers\rfi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\MikrotikAPI\Routeros_api;
use App\Requests;
use App\Mkxpe;
use App\Logs;
use App\Accesses;
use App\Events\ApproveInternet;
use App\Events\DeclineInternet;
use App\Events\MarkAccess;

class AdminRequestController extends Controller
{
    protected $routeros_api;

    public function __construct(Routeros_api $routeros_api){

        

        $this->routeros_api = $routeros_api;
        // $this->routeros_api->connect($cx->mkip, $cx->mkau, $cx->mkpw);
   }
    public function index(){
        $forApproval = Requests::where('status', 'pending')->get();

        return view('rfi.admin')->with('forApprovel', $forApproval);
    }

    public function update($action, $id){
		
		         
        if($action == 'decline'){
            $request = Requests::find($id);
            $request->status = 'decline';
            $request->save();

            event(new DeclineInternet($request));

            return redirect(route('requestAdminDash'))->with('success', 'Request successfully declined!');
        }elseif($action == 'approve'){

            $request = Requests::find($id);

            

            $t = str_random('7');
            $username = $request->hours.$t;

            $cx = Mkxpe::find(1);
            
            $this->routeros_api->connect($cx->mkip, $cx->mkau, $cx->mkpw);

            $users =  $this->routeros_api->comm("/ppp/secret/print");

            
            
			
			//dd($users);

            // $this->routeros_api->comm("/ppp/secret/add", array(
                                    // //"customer" => 'xonivre',
                                    // "username" => $username, 
                                    // "password" => $username,
                                    // "first-name" => $request->requestor_name,
                                    // "last-name" => $request->requestor_ip,
                                    // "comment" => $request->purpose
                                 // ));
            $profile = '';

            // change this also in the RequestController.php
            // switch($request->hours){
                // case '1h':
                    // $profile = '1MB1hourConn';
                // break;
                // case '2h':
                    // $profile = '1MB2hourConn';
                // break;
                // case '3h':
                    // $profile = '1MB3hoursConn';
                // break;
                // case '8h':
                    // $profile = '1MB8hourConn';
            // }
			
			switch($request->hours){
                case '1h':
                    $profile = '1MB_Connection';
                break;
                case '2h':
                    $profile = '5MB_Connection';
                break;
                case '3h':
                    $profile = '5MB_Connection';
                break;
                case '8h':
                    $profile = '50MB_Connection';
            }
			
			 // $this->routeros_api->comm("/ppp/secret/add", array(
                                    // //"customer" => 'xonivre',
                                    // "username" => $username, 
                                    // "password" => $username,
                                    // "first-name" => $request->requestor_name,
                                    // "last-name" => $request->requestor_ip,
                                    // "comment" => $request->purpose
                                 // ));
    
            $set = $this->routeros_api->comm("/ppp/secret/add", array(
                                    //'numbers' => count($users),
									'name' => $username,
									"password" => $username,
									'service' => 'pppoe',
                                    'profile' => $profile,
									'comment' => $request->requestor_name.'('.$request->requestor_ip.') purpose:'. $request->requestor_purpose
                                    //'customer' => 'xonivre'
                                ));

            $access = new Accesses;
            $access->username = $username;
            $access->password = $username;
            $access->hours = $request->hours;
            $access->requests_id = $id;
            $access->approve_by = $_SERVER['REMOTE_ADDR']; // change this to login admin
            $access->approve_by_ip = $_SERVER['REMOTE_ADDR'];
            $access->save();

            $request->status = 'approve';
            $request->accesses_id = $access->id;
            $request->save();

            event(new ApproveInternet($request));
                      
            exec('C:/xampp/htdocs/integrated/ipmsg.exe /MSG '.$request->requestor_ip.' Your request has been approved. To login use '.$username.' as your username and password');
            return redirect(route('requestAdminDash'))->with('success', 'Request successfully approve with '.$username.' access code.');

        }else{
            return redirect(route('requestAdminDash'));
        }

    }

    public function history(){
        $request = Requests::where('status', '!=', 'pending')->orderBy('created_at', 'DESC')->paginate(20);

        return view('rfi.adminHistory')->with('request', $request);
    }

    public function access(){
        $accesses = Accesses::orderBy('created_at','DESC')->get();
        
        //die();

        return view('rfi.adminAccess')->with('accesses', $accesses);
    }

    public function mikrotik(){
        $cx = Mkxpe::find(1);
        $this->routeros_api->connect($cx->mkip, $cx->mkau, $cx->mkpw);

        
		$accesses =  $this->routeros_api->comm("/ppp/secret/print");
		
		//$mt = $this->routeros_api->comm("/ppp/active/print", array('from' => 'xonivre'));

       //dump($accesses);

        return view('rfi.adminAccessMT')->with('accesses', $accesses);
    }

    public function destroy($username){
		
		
       
        $cx = Mkxpe::find(1);
		
        $this->routeros_api->connect($cx->mkip, $cx->mkau, $cx->mkpw);

        //$access = Accesses::where(array('username'=>$username, 'is_used'=>'No'))->first(); //using where to get the right item, username might be created again
        $access = Accesses::where(array('username'=>$username))->first();
		
		//dd($access);
		
       //dd($access);
        $acc = Accesses::find($access->id);
        $acc->hours = $access->hours;
        $acc->is_used = 'Yes';
        $acc->save();

        event(new MarkAccess($acc));

        $mt = $this->routeros_api->comm("/ppp/secret/print", array('from' => $username));
		
		//dd($mt);
		
        $remove = $this->routeros_api->comm("/ppp/secret/remove", array(".id" => $mt[0]['.id']));
        return redirect(route('requestAdminAccessMT'))->with('success', 'Access('.$username.') has been remove.');
    }


    public function IpRoute(){

        $cx = Mkxpe::find(1);
        $this->routeros_api->connect($cx->mkip, $cx->mkau, $cx->mkpw);

        
		$routes =  $this->routeros_api->comm("/bridge/ports/print");

        dump($routes);

    }


}
