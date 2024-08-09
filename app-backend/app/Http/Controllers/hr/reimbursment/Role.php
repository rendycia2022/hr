<?php

namespace App\Http\Controllers\hr\reimbursment;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Role extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    function get(Request $request){

        $payload_id = $request->input('id');

        $role_user = DB::table('users')
        ->where('id',$payload_id)
        ->first();

        $query = DB::table('role')
        ->where('id','>=',$role_user->role)
        ->orderby('id','DESC')
        ->get();

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "data"=>$query,
            )
        );

        return response()->json($response);
        
    }

    function role_user(Request $request){

        $payload_id = $request->input('id');

        $role_user = DB::table('users')
        ->where('id',$payload_id)
        ->first();
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "data"=>$role_user->role,
            )
        );

        return response()->json($response);
        
    }
}

?>