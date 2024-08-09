<?php

namespace App\Http\Controllers\hr\reimbursment;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// models
use App\Models\reimbursement\TransactionModel;

class Approval extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->TransactionModel = new TransactionModel;
    }

    function options(Request $request){

        $query = DB::table('users')
        ->where('active',1)
        ->get();
        $result = array();

        foreach($query as $list){
            $result[] = array(
                "name"=>$list->email,
                "code"=>$list->id,
            );
        };
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "data"=>$result,
            )
        );

        return response()->json($response);
        
    }

    function get(Request $request){

        $payload_id = $request->input('id');

        $query = DB::table('approval')
        ->where('approval', $payload_id)
        ->where('active',1)
        ->first();

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "data"=>$query,
            )
        );

        return response()->json($response);
        
    }

    function getById(Request $request){

        $payload_id = $request->input('id'); //transaction id

        $query = $this->TransactionModel->status_approval_byId($payload_id);

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "data"=>$query,
            )
        );

        return response()->json($response);
        
    }
}

?>