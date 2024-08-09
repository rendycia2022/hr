<?php

namespace App\Http\Controllers\hr\reimbursment\data;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Company extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    function get(Request $request){

        $data = DB::table('company')
        ->select("id","name") 
        ->where('active', 1)
        ->get();

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Success",
                "data"=>$data,
            )
        );

        return response()->json($response);
        
    }
}

?>