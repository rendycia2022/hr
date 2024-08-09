<?php

namespace App\Http\Controllers\auth;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class TokenController extends BaseController
{

    function get(Request $request){

        //define data
        $user_id = $request->input('user_id');
        $token = $request->input('token');
        
        $query = DB::table('login')
        ->select(
            'login.user_id',
            'login.token',
            'login.created_at',
        )
        ->where('login.user_id',$user_id)
        ->where('login.token',$token)
        ->get();

        $data_login = array();
        if($query){
            $status = 200;
            $message = "Success";
            foreach($query as $list){
                $data_login = array(
                    "user_id"=>$list->user_id,
                    "token"=>$list->token,
                    "created_at"=>$list->created_at,
                );
            }
        }else{
            $status = 401;
            $message = "Forbidden";
        }
        
        $response = array(
            "status"=>$status,
            "message"=>$message,
            "metadata"=>$data_login,
        );

        return response()->json($response);
        
    }

}

?>