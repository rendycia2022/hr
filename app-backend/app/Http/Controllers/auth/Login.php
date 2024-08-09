<?php

namespace App\Http\Controllers\auth;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

class Login extends BaseController
{
    function checkData($params){
        $query = DB::table('users')
        ->where('email',$params)
        ->where('active',"1")
        ->first();

        return $query;
    }

    function get(Request $request){
        
        $params = $request->json()->all();
        $query = $this->checkData($params['email']);

        if($query){
            $response = array(
                "status"=>"200",
                "metadata"=>array(
                    "id"=>$query->id
                )
            );
        }else{
            $response = array(
                "status"=>"404",
                "metadata"=>array(
                    "message"=>"Not Found"
                )
            );
        }

        return response()->json($response);
        
    }

    function store(Request $request){

        $params = $request->json()->all();
        $query = $this->checkData($params['email']);

        if($query){
            if (Hash::check($params['password'], $query->password)) {
                // if password is correct
                // create token
                $token = Uuid::uuid1();
                $store = array($query->id,$token,$request->getClientIp(),);

                DB::table('login')->where('user_id',$query->id)->delete(); //delete existing data

                DB::insert('insert into login (user_id, token, ip_address) values (?, ?, ?)', $store);
                
                $response = array(
                    "status"=>"200",
                    "metadata"=>array(
                        "id"=>$query->id,
                        "email"=>$query->email,
                        "role"=>$query->role,
                        "token"=>$token,
                    )
                );
            }else{
                // if password is wrong
                $response = array(
                    "status"=>"403",
                    "metadata"=>array(
                        "message"=>"Forbiden"
                    )
                );
            }
        }else{
            // if email not found
            $response = array(
                "status"=>"404",
                "metadata"=>array(
                    "message"=>"Not found"
                )
            );
        }
        
        return response()->json($response);
        
    }

    function put(Request $request){

        $params = $request->json()->all();
        $query = $this->checkData($params['email']);

        if($query){
            if (Hash::check($params['password'], $query->password)) {
                // if password is correct
                $password = Hash::make($params['new_password']);
        
                $update = DB::table('users')
                    ->where('email', $params['email'])
                    ->update(['password' => $password]);

                if($update){

                    $response = array(
                        "status"=>"200",
                        "metadata"=>array(
                            "message"=>"Success"
                        )
                    );

                }else{

                    $response = array(
                        "status"=>"500",
                        "metadata"=>array(
                            "message"=>"Internal Server Error"
                        )
                    );

                }
            }else{
                // if password is wrong
                $response = array(
                    "status"=>"403",
                    "metadata"=>array(
                        "message"=>"Forbiden"
                    )
                );
            }
        }

        return response()->json($response);
    }
}

?>