<?php

namespace App\Http\Controllers;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class Generator extends BaseController
{
    function getId_class1(Request $request, $total_data){

        for($i=0; $i<$total_data; $i++){
            $id[] = Uuid::uuid1();
        };
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "data"=>$id,
            )
        );

        return response()->json($response);
        
    }

    function getId_class4(Request $request, $total_data){

        for($i=0; $i<$total_data; $i++){
            $id[] = Uuid::uuid4();
        };
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "data"=>$id,
            )
        );

        return response()->json($response);
        
    }
}

?>