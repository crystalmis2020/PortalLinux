<?php

namespace App\Http\Controllers\rfi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\MikrotikAPI\Routeros_api;
use App\Requests;
use App\Logs;
use App\Mkxpe;
use App\Accesses;
use App\Events\RequestInternet;
use App\Events\ApproveInternet;

class RequestController extends Controller
{
    protected $routeros_api;

    public function __construct(Routeros_api $routeros_api){

        $this->routeros_api = $routeros_api;
        // $this->routeros_api->connect($cx->mkip, $cx->mkau, $cx->mkpw);
   }

    public function index(){

        $requests = Requests::where('requestor_ip', $_SERVER['REMOTE_ADDR'])->get();
        //dd($requests);
        return view('rfi.index')->with('requests', $requests);

    }

    public function store(Request $request){
		
		//dd($request->n);

        if($request->n > 0):

            $requests = Requests::where('requestor_ip', $_SERVER['REMOTE_ADDR'])->where('status', 'approve')->orderBy('created_at', 'desc')->get()->first();

            // $cx = Mkxpe::find(1);
            // $this->routeros_api->connect($cx->mkip, $cx->mkau, $cx->mkpw);

            // $username = $requests->accesses->username;
            // //dd($requests->accesses->hours);
			
			// //dd($this->routeros_api->comm("/tool/user-manager/user/print", array('from' => $username)));
			
            // $mt = $this->routeros_api->comm("/tool/user-manager/user/print", array('from' => $username));
            // $remove = @$this->routeros_api->comm("/tool/user-manager/user/remove", array(".id" => $mt[0]['.id']));

            $acc = Accesses::find($requests->accesses_id);
            $acc->used_uptime = $requests->accesses->hours;
            $acc->is_used = 'Yes';
            $acc->save();

        endif;
        $this->validate($request, [
            'name' => 'required',
            'purpose' => 'required'
        ]);
        //$t = str_random('7');
        //$username = $request->hours.$t;

       // dd($request);

        $requests = new Requests;
        //$requests->username = $username;
       // $requests->password = $username;
        $requests->hours = $request->hours;
        $requests->requestor_ip = $_SERVER['REMOTE_ADDR'];
        $requests->requestor_name = $request->name;
        $requests->requestor_purpose = $request->purpose;
        $requests->save();

        event(new RequestInternet($requests));
       
       // exec('D:/xampp/htdocs/integratedLaravel/ipmsg.exe /MSG 128.0.100.14 A user is requesting to access the internet. ('.$request->name.')');
        exec('C:/xampp/htdocs/integrated/ipmsg.exe /MSG 128.0.11.12 A user is requesting to access the internet. ('.$request->name.')');
		exec('C:/xampp/htdocs/integrated/ipmsg.exe /MSG 128.0.100.78 A user is requesting to access the internet. ('.$request->name.')');
		//exec('C:/xampp/htdocs/integrated/ipmsg.exe /MSG 128.0.100.247 A user is requesting to access the internet. ('.$request->name.')');

        return redirect(route('requestForApproval', $requests->id))->with('success', 'Request sent');

    }

    public function forApproval($id){
        $request = requests::find($id);

        

        $timeDiff = $request->created_at->diffInMinutes(date('Y-m-d h:i:s'));
        
        // print_r($request->created_at);
        // print_r(date('Y-m-d h:i:s'));
		
		// dd($timeDiff);
		
		
        if($timeDiff > 2 && $request->status == 'pending'){

            $t = str_random('7');
            $username = $request->hours.$t;

            $cx = Mkxpe::find(1);
            $this->routeros_api->connect($cx->mkip, $cx->mkau, $cx->mkpw);

            //$users =  $this->routeros_api->comm("/ppp/secret/print");

            // $this->routeros_api->comm("/tool/user-manager/user/add", array(
                                    // "customer" => 'xonivre',
                                    // "username" => $username, 
                                    // "password" => $username,
                                    // "first-name" => $requests->requestor_name,
                                    // "last-name" => $requests->requestor_ip,
                                    // "comment" => $requests->purpose
                                 // ));
            $profile = '';

            // switch($requests->hours){
            //     case '1h':
            //         $profile = '1MB1hourConn';
            //     break;
            //     case '2h':
            //         $profile = '1MB2hourConn';
            //     break;
            //     case '3h':
            //         $profile = '1MB3hoursConn';
            //     break;
            //     case '8h':
            //         $profile = '1MB8hourConn';
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

            $set = $this->routeros_api->comm("/ppp/secret/add", array(
                                    //'numbers' => count($users),
									'name' => $username,
									"password" => $username,
									'service' => 'pppoe',
                                    'profile' => $profile,
									'comment' => $request->requestor_name.'('.$request->requestor_ip.') purpose:'. $request->requestor_purpose
                                    //'customer' => 'xonivre'
                                ));

            print_r($set);

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

            $log = new Logs;
            $log->message = 'An Internet request was automatically approve due admin inactivity.';
            $log->table = 'requests';
            $log->table_id = $id;
            $log->user = $_SERVER['REMOTE_ADDR']; // change this to login admin
            $log->ip = $_SERVER['REMOTE_ADDR'];
            $log->save();
			
			exec('C:/xampp/htdocs/integrated/ipmsg.exe /MSG '.$request->requestor_ip.' Your request has been approved. To login use '.$username.' as your username and password');
            exec('C:/xampp/htdocs/integrated/ipmsg.exe /MSG 128.0.11.12 An Internet request was automatically approve due admin inactivity.');
            exec('C:/xampp/htdocs/integrated/ipmsg.exe /MSG 128.0.100.78 An Internet request was automatically approve due admin inactivity.');
            //exec('C:/xampp/htdocs/integrated/ipmsg.exe /MSG 128.0.100.247 An Internet request was automatically approve due admin inactivity.');

        }

        return view('rfi.forApproval')->with("requests",$request);

    }
}
