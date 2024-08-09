<?php

namespace App\Http\Controllers\development;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Mail;
// use App\Mail\ForgotPassword;

class GenerateIdController extends BaseController
{
    function id_class1(Request $request, $total_data){
        //time base unique id

        for($i=0; $i<$total_data; $i++){
            $id[] = Uuid::uuid1();
        };
        
        $response = array(
            "status"=>200,
            "metadata"=>array(
                "message"=>$id,
            )
        );

        return response()->json($response);
        
    }

    function id_class4(Request $request, $total_data){
        //random base unique id

        for($i=0; $i<$total_data; $i++){
            $id[] = Uuid::uuid4();
        };
        
        $response = array(
            "status"=>200,
            "metadata"=>array(
                "message"=>$id,
            )
        );

        return response()->json($response);
        
    }

}

?>