<?php

namespace App\Http\Controllers\auth;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForgotPassword;

class Forgot extends BaseController
{
    function store(Request $request){

        $params = $request->json()->all();


        // developing code start
        
        $query = DB::table('users')
        ->select('users.email','employee.name')
        ->leftjoin('employee','users.id', '=', 'employee.user_id')
        ->where('users.email',$params['email'])
        ->where('users.active','1')
        ->first();

        if(isset($query)){
            $host = 'http://103.188.175.175:5185';

            $details = [
                'home_apps'=>$host,
                'host_apps_frontend'=>$host.'/auth/forgot/'.$query->email.'/result', 
                'user_email' => $query->email,
                'user_name' =>$query->name,
            ];

            Mail::to($query->email)->send(new ForgotPassword($details));

            $status = "200";
            $summary = "Successful";
            $severity = "success";
            $message = "Email reset password sent to ".$query->email;

        }else{

            $status = "404";
            $summary = "Failed";
            $severity = "error";
            $message = "Email ".$params['email']." not found.";

        }

        $response = array(
            "status"=>$status,
            "summary"=>$summary,
            'severity'=>$severity,
            "metadata"=>array(
                "message"=>$message,
                "email"=>$params['email'],
            )
        );
        
        return response()->json($response);
        
    }

    function put(Request $request){

        $params = $request->json()->all();

        $query = DB::table('users')
        ->select('id')
        ->where('email',$params['email'])
        ->where('active','1')
        ->first();

        if(isset($query)){
            DB::table('users')
            ->where('id', $query->id)->update(
                [
                    'password'=>Hash::make('123456'),
                    'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                ]
            );

            $message = 'Password has been reset, please login first.';
            $status = 200;
        }else{
            $message = 'Account not found, please contact your Administrator.';
            $status = 404;
        }
        

        $response = array(
            "status"=>$status,
            "metadata"=>array(
                "message"=>$message,
                "email"=>$params['email'],
            )
        );
        
        return response()->json($response);
    }
}

?>