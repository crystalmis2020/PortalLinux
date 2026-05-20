<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\MikrotikAPI\Routeros_api;

use App\Mkxpe;

class UsersController extends Controller
{
    
    protected $routeros_api;

    public function __construct(Routeros_api $routeros_api){

        $cx = Mkxpe::find(1);

        $this->routeros_api = $routeros_api;
        $this->routeros_api->connect($cx->mkip, $cx->mkau, $cx->mkpw);
   }
    public function index(){

        $users =  $this->routeros_api->comm("/tool/user-manager/user/print");
      
       
        return view('users.index')->with('users', $users);
    }

    public function create(){

        $profiles =  $this->routeros_api->comm("/tool/user-manager/profile/print");

        return view('users.create')->with('profiles', $profiles);
    }

    public function store(Request $request){
       
        /*
        |--------------------------------------------------------------------------
        | For Future Development
        |--------------------------------------------------------------------------
        |
        | Check database if username is already exist
        | Username should be always unique even though it has been created before
        | All data should be save on a database (user login details, user IP login into)
        |
        */

        $this->validate($request, [
            'username' => 'required',
            'password' => 'required'
        ]);
        
        $users =  $this->routeros_api->comm("/tool/user-manager/user/print");

        $this->routeros_api->comm("/tool/user-manager/user/add", array(
                                "customer" => 'xonivre',
                                "username" => $request->username, 
                                "password" => $request->password,
                            ));

        $test = $this->routeros_api->comm("/tool/user-manager/user/create-and-activate-profile", array(
                                'numbers' => count($users),
                                'profile' => $request->actual_profile,
                                'customer' => 'xonivre'
                                ));
        

        return redirect('/users')->with('success', 'New User successfully created! ('.$request->username.')');
    }

    public function destroy($id,$username){
        /*
        |--------------------------------------------------------------------------
        | For Future Development
        |--------------------------------------------------------------------------
        |
        | Check database if username exist
        | No data should be remove on the database
        | Mark data as deleted, log user who deleted the username (user login details, user IP login into)
        |
        */
       
        $test = $this->routeros_api->comm("/tool/user-manager/user/remove", array(".id" => $id));
        return redirect('/users')->with('success', 'User successfully deleted! ('.$username.')');
    }

}
