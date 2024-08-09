<?php

namespace App\Http\Controllers\hr\reimbursment;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class Plafon extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    function get(Request $request){

        $query = DB::table('plafon')->where('active',1)->get();
        $result = array();

        foreach($query as $list){
            $query2 = DB::table('jobs')->where('id',$list->job_level)->first();
            
            $result[] = array(
                "id"=>$list->id,
                "job"=>array(
                    "code"=>$list->job_level,
                    "level"=>$query2->level,
                    "grade"=>$query2->grade,
                ),
                "job_level"=>$query2->level,
                "job_grade"=>$query2->grade,
                "item"=>$list->item,
                "plafon"=>$list->amount,
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

    function getById($id){

        $query = DB::table('plafon')
        ->where('id',$id)
        ->where('active',1)
        ->get();
        
        $result = array();

        foreach($query as $list){
            $query2 = DB::table('jobs')->where('id',$list->job_level)->first();
            
            $result = array(
                "id"=>$list->id,
                "job"=>array(
                    "code"=>$list->job_level,
                    "level"=>$query2->level,
                    "grade"=>$query2->grade,
                ),
                "job_level"=>$query2->level,
                "job_grade"=>$query2->grade,
                "item"=>$list->item,
                "plafon"=>$list->amount,
            );
        };
        
        return $result;
        
    }

    function getByUser(Request $request){

        $payload_id = $request->input('id');

        $query = DB::table("employee")
        ->where("user_id", $payload_id)
        ->get(); 

        $result = array();
        $job = array();

        foreach($query as $list){
            $job = $list->job;

            // date define
            $date_join_raw = $list->date_join;
            $today = gmdate('Y-m-d', time()+(60*60*7));

            $query2 = DB::table("plafon")
            ->select('plafon.id',
            'plafon.amount',
            'plafon.item',
            'plafon_setting.limit_emerge',
            'plafon_setting.emerge_limit',
            'plafon_setting.emerge_year',
            'plafon_setting.updated_at'
            )
            ->leftjoin('plafon_setting','plafon.id','=','plafon_setting.plafon_id')
            ->whereDate('plafon_setting.effective_date','<=',$today)
            ->where('plafon.job_level', $job)
            ->where('active',1)
            ->get();

            foreach($query2 as $list2){

                // set limit emerge
                $date_join = date_create($list->date_join);
                $limit_emerge = $list2->limit_emerge;
                date_add($date_join,date_interval_create_from_date_string($limit_emerge." months"));
                $date_emerge = date_format($date_join,"Y-m-d");

                if($date_emerge <= $today){

                    // define emerge limit item every n year start
                    $plafon_id = $list2->id;
                    $plafon_emerge_limit = $list2->emerge_limit;
                    $plafon_emerge_year = $list2->emerge_year;
                    $plafon_updated_at = $list2->updated_at;
                    
                    // define emerge limit item every n year end
                    
                    if($plafon_emerge_limit === null || $plafon_emerge_year === null || $plafon_emerge_limit == 0 || $plafon_emerge_year == 0){
                        $result[] = array(
                            "code"=>$list2->id,
                            "id"=>$list2->id,
                            "item"=>$list2->item,
                            "name"=>$list2->item,
                            "amount"=>$list2->amount,
                        );
                    }else{
                        $checkTransactionLimit = $this->checkTransactionLimit($plafon_id, $payload_id, $list->date_join, $plafon_emerge_limit, $plafon_emerge_year, $plafon_updated_at, $today);
                        
                        if($checkTransactionLimit == 1){
                            $result[] = array(
                                "code"=>$list2->id,
                                "id"=>$list2->id,
                                "item"=>$list2->item,
                                "name"=>$list2->item,
                                "amount"=>$list2->amount,
                                "transaction"=>true,
                            );
                        }
                    }
                }
            };

            $query3 = DB::table("jobs")
            ->where("id", $job)
            ->get();

            foreach($query3 as $list3){
                $job = array(
                    "level"=>$list3->level,
                    "grade"=>$list3->grade,
                );
            };
        };
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "data"=>$result,
                "job"=>$job,
            )
        );

        return response()->json($response);
        
    }

    function checkTransactionLimit($plafon_id, $user_id, $employee_join_date, $plafon_emerge_limit, $plafon_emerge_year, $plafon_updated_at, $today){

        $date = date_create($employee_join_date); //join date

        $transaction = DB::table("reimbursment_request")
        ->select('id','created_at')
        ->where("plafon_id", $plafon_id)
        ->where("user_id", $user_id)
        ->whereIn("status_id", ['0','2','4'])
        ->whereDate('created_at','>=',$plafon_updated_at)
        ->orderby('created_at','DESC')
        ->get();

        $count = count($transaction);

        if($count > 0){
            $count_transaction = count($transaction);
            if($count_transaction < $plafon_emerge_limit){
                $result = 1;
            }else{
                $interval_limit = $count_transaction % $plafon_emerge_limit;
                if($interval_limit == 0){ // check if transaction greater than $plafon_emerge_limit
                    $last_transaction = DB::table("reimbursment_request")
                        ->select('id','created_at')
                        ->where("plafon_id", $plafon_id)
                        ->where("user_id", $user_id)
                        ->whereIn("status_id", ['0','2','4'])
                        ->orderby('created_at','DESC')
                        ->first();
                    
                    $last_date = date_create($last_transaction->created_at);
                    date_add($last_date,date_interval_create_from_date_string($plafon_emerge_year." years"));
                    $last_day_formated = date_format($last_date,"Y-m-d");
                    if($today > $last_day_formated){
                        $result = 1; // over time limit periode
                    }else{
                        $result = 0; // on limit periode
                    }
                }else{
                    $result = 1; // interval not 0
                }
            }
        }else{
            $result = 1;
        }

        // $result = 0 is disable, $result = 1 is enable
        return $result;
    }

    function store(Request $request){

        $payload = $request->json()->all();

        $id = Uuid::uuid1();

        DB::table('plafon')->insert(
            [
                'id'=>$id,
                'job_level' => $payload['job']['code'],
                'item' => $payload['item'],
                'amount' => $payload['plafon'],
                'created_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
            ]
        );

        $getById = $this->getById($id);

        DB::table('plafon_setting')->insert(
            [
                'plafon_id'=>$id,
                'created_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                'created_by'=>$payload['setting']['user_id'],
                'updated_by'=>$payload['setting']['user_id'],
                'expired_day'=> $payload['setting']['expired']['day'],
                'expired_month'=> $payload['setting']['expired']['month'],
                'limit_emerge'=> $payload['setting']['limit']['emerge'],
                'limit_emerge'=> $payload['setting']['limit']['emerge'],
                'effective_date'=>$payload['setting']['effective_date'],
                'emerge_limit'=>$payload['setting']['emerge']['limit'],
                'emerge_year'=>$payload['setting']['emerge']['year'],
            ]
        );

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "data"=>$getById,
            )
        );

        return response()->json($response);
        
    }

    function put(Request $request){ 

        $payload = $request->json()->all();

        DB::table('plafon')
        ->where('id', $payload['id'])->update(
            [
                'job_level' => $payload['job']['code'],
                'item' => $payload['item'],
                'amount' => $payload['plafon'],
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
            ]
        );

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
            )
        );

        return response()->json($response);
        
    }

    function destroy(Request $request){

        $payload = $request->json()->all();

        DB::table('plafon')
        ->where('id', $payload['data']['id'])->update(
            [
                'active' =>0,
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
            ]
        );

        DB::table('plafon_setting')->where('plafon_id', $payload['data']['id'])->delete();

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
            )
        );

        return response()->json($response);
        
    }

    function destroyBurst(Request $request){

        $payload = $request->json()->all();
        $count = count($payload['data']);
        for($i=0; $i<$count; $i++){
            $id[] = $payload['data'][$i]['id'];
        };

        DB::table('plafon')->whereIn('id', $id)->update(
            [
                'active' =>0,
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
            ]
        );

        DB::table('plafon_setting')->whereIn('plafon_id', $id)->delete();

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Success",
            )
        );

        return response()->json($response);
        
    }
}

?>