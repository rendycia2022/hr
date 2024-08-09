<?php

namespace App\Http\Controllers\hr\reimbursment;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class Plafon_setting extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    function getById(Request $request){ 

        $payload_plafon_id = $request->input('plafon_id');

        $query = DB::table("plafon_setting")
        ->where("plafon_id", $payload_plafon_id)
        ->first();

        $result = array(
            "effective_date"=>'',
            "expired"=>array(
                "day"=>'',
                "month"=>'',
            ),
            "limit"=>array(
                "emerge"=>'',
            ),
            "emerge"=>array(
                "limit"=>null,
                "year"=>null,
            ),
        );

        if(isset($query)){
            $result = array(
                "effective_date"=>$query->effective_date,
                "expired"=>array(
                    "day"=>$query->expired_day,
                    "month"=>$query->expired_month,
                ),
                "limit"=>array(
                    "emerge"=>$query->limit_emerge,
                ),
                "emerge"=>array(
                    "limit"=>$query->emerge_limit,
                    "year"=>$query->emerge_year,
                ),
            );
        }
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "setting"=>$result,
            )
        );

        return response()->json($response);
        
    }

    function put(Request $request){ 

        $payload = $request->json()->all();

        DB::table('plafon_setting')
        ->where('plafon_id', $payload['plafon_id'])->update(
            [
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                'updated_by'=>$payload['user_id'],
                'expired_day'=> $payload['expired']['day'],
                'expired_month'=> $payload['expired']['month'],
                'limit_emerge'=> $payload['limit']['emerge'],
                'limit_emerge'=> $payload['limit']['emerge'],
                'effective_date'=>$payload['effective_date'],
                'emerge_limit'=>$payload['emerge']['limit'],
                'emerge_year'=>$payload['emerge']['year'],
            ]
        );

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"updated",
                "payload"=>$payload,
            )
        );

        return response()->json($response);
        
    }
}

?>