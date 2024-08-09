<?php

namespace App\Http\Controllers\hr\reimbursment;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Jobs extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    function get(Request $request){

        $query = DB::table('jobs')
        ->orderBy('order', 'ASC')
        ->get();
        $result = array();

        foreach($query as $list){
            $result[] = array(
                "name"=>$list->level." - ".$list->grade,
                "code"=>$list->id,
                "level"=>$list->level,
                "grade"=>$list->grade,
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
}

?>