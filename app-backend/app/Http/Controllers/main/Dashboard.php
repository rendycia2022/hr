<?php

namespace App\Http\Controllers\main;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Dashboard extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    function get(Request $request){
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Login success"
            )
        );

        return response()->json($response);
        
    }
}

?>