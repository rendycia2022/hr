<?php

namespace App\Http\Controllers\auth;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

class Signup extends BaseController
{
    // gmdate('Y-m-d H:i:s', time()+(60*60*7));

    function checkData($params){
        $query = DB::table('users')
        ->where('email',$params)
        ->first();

        return $query;
    }
    
    function get(Request $request){

        $params = $request->json()->get('email');

        $query = $this->checkData($params);

        if(empty($query)){
            $response = array(
                "status"=>"200",
                "metadata"=>array(
                    "message"=>"Available"
                )
                
            );
        }else{
            $response = array(
                "status"=>"400",
                "metadata"=>array(
                    "message"=>"Not Available"
                )
            );
        }

        return response()->json($response);
        
    }

    function store(Request $request){

        // check email availability
        $email = $request->json()->get('email');
        $availability = $this->checkData($email);

        if($availability){
            $response = array(
                "status"=>"400",
                "metadata"=>array(
                    "message"=>"Bad Request",
                )
            );
        }else{
            $data = $request->json()->all();
            
            // Collect data to store into database
            $password = Hash::make($data['password']); // hashing password data
            $date_now = gmdate('Y-m-d H:i:s', time()+(60*60*7));

            DB::table('users')->insert(
                [
                    'id'=>Uuid::uuid4(),
                    'email' => $email,
                    'password' => $password,
                    'name' => $data['name'],
                    'created_at' => $date_now,
                    'updated_at' => $date_now,
                ]
            );
            
            $stored = $this->checkData($email);
            if($stored){
                $result = array(
                    "name"=>$stored->name,
                    "email"=>$stored->email,
                    "created_at"=>$stored->created_at,
                );
                $response = array(
                    "status"=>"200",
                    "metadata"=>array(
                        "message"=>$result,
                    )
                );
            }else{
                $response = array(
                    "status"=>"500",
                    "metadata"=>array(
                        "message"=>"Internal Server Error",
                    )
                );
            }
        }
        
        return response()->json($response);
        
    }
}

?>
