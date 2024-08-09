<?php

namespace App\Http\Controllers\hr\reimbursment;
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

        $payload_id = $request->input('id');

        $query = DB::table('employee')
        ->where('user_id',$payload_id)
        ->first();
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Login success",
                "data"=>$query,
                "host"=>env('APP_HOST'),
            )
        );

        return response()->json($response);
        
    }

    function get_approval(Request $request){ 

        $payload_id = $request->input('id');

        $query = DB::table('reimbursment_approval')
        ->select('reimbursment_request.user_id','reimbursment_request.id as transaction_id')
        ->leftjoin("reimbursment_request","reimbursment_request.id","=","reimbursment_approval.request_id")
        ->where('reimbursment_request.status_id', 0)
        ->where('reimbursment_approval.status_id', 0)
        ->where('reimbursment_approval.approval_id', $payload_id)
        ->get();

        $count = 0;
        $rawData = array();
        foreach($query as $list){
            $user_transaction = $list->user_id;

            //get approval from transaction user_id
            $query2 = DB::table('approval')
            ->select('approval','sort')
            ->where('active',1)
            ->where('user_id', $user_transaction)
            ->where('approval', $payload_id)
            ->first();

            if(isset($query2)){
                $sort = $query2->sort;
                if($sort > 1){
                    $sort = $sort-1;

                    // search approval this user where sort = $sort
                    $query3 = DB::table('approval')
                    ->select('approval','sort')
                    ->where('active',1)
                    ->where('user_id', $list->user_id)
                    ->where('sort', $sort)
                    ->first();

                    //get status_id from transaction and approval greater than this user
                    if(isset($query3->approval)){
                        $query4 = DB::table('reimbursment_approval')
                        ->select('request_id','approval_id','status_id')
                        ->where('request_id',$list->transaction_id)
                        ->where('approval_id',$query3->approval)
                        ->first();

                        // if status = 2 or approved display data
                        if(isset($query4)){
                            if($query4->status_id == 2){
                                $count++;
                                $rawData[] = array(
                                    "transaction_id"=>$list->transaction_id,
                                    "status"=>$query4->status_id,
                                );
                            }
                        } 
                    }
                    
                }else{
                    $count++;
                    $rawData[] = array(
                        "transaction_id"=>$list->transaction_id,
                    );
                }
            }
        }

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "count"=>$count,
                "raw"=>$rawData,
            )
        );

        return response()->json($response);
        
    }

    function get_paid(Request $request){

        // get transaction with status pending and not paid
        $query = DB::table('reimbursment_request')
        ->where('reimbursment_request.status_id', 0)
        ->where('reimbursment_request.status_id','!=', 4)
        ->get();

        $count = 0;
        $alldata = array();
        foreach($query as $list){
            $request_id = $list->id;
            
            // find status all approved 
            $query2 = DB::table('reimbursment_approval')
            ->select('id','status_id')
            ->where('request_id', $request_id)
            ->get();
            $status_list = array();
            foreach($query2 as $list2){
                $status_list[] = $list2->status_id;
            }
            if(empty($status_list) || in_array("0", $status_list) || in_array("3", $status_list)){
                continue;
            }else{
                $count=$count+1;
                $alldata[] = array(
                    "id"=>$list->id,
                    "transaction"=>$list->request_id,
                    "status"=>$status_list,
                );
            }
        }

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "count"=>$count,
                "raw"=>$alldata,
            )
        );

        return response()->json($response);
        
    }

    function get_plafon(Request $request){ // item in dashboard
        
        $user_id = $request->input('user_id');
        $result = array();
        
        // get job level from employee
        $query = DB::table('employee')
        ->select('job','date_join')
        ->where('user_id', $user_id)
        ->first();

        // date define
        $date_join_raw = $query->date_join;
        $today = gmdate('Y-m-d', time()+(60*60*7));

        // get plafon from job in employee
        $query2 = DB::table('plafon')
        ->select('plafon.id','plafon.amount','plafon.item','plafon_setting.limit_emerge')
        ->join('plafon_setting','plafon.id','=','plafon_setting.plafon_id')
        ->whereDate('plafon_setting.effective_date','<=',$today)
        ->where('plafon.job_level', $query->job)
        ->where('active',1)
        ->get();

        // build data plafon
        foreach($query2 as $list2){
            $item = $list2->item;
            $base_plafon = $list2->amount;
            $plafon_id = $list2->id;

            // set limit emerge
            $date_join = date_create($query->date_join);
            $limit_emerge = $list2->limit_emerge;
            date_add($date_join,date_interval_create_from_date_string($limit_emerge." months"));
            $date_emerge = date_format($date_join,"Y-m-d");

            if($date_emerge <= $today){

                $year_now = gmdate('Y', time()+(60*60*7));

                //get total amount start
                $query3 = DB::table('reimbursment_request')
                ->select('reimbursment_request_detail.amount')
                ->join("reimbursment_request_detail","reimbursment_request_detail.request_id","=","reimbursment_request.id")
                ->where('reimbursment_request.status_id', 4)
                ->where('reimbursment_request.user_id', $user_id)
                ->where('reimbursment_request.plafon_id', $plafon_id) 
                ->WhereYear('reimbursment_request.created_at',$year_now) // harusnya submit date
                ->get();

                $total_amount = 0;
                
                foreach($query3 as $list3){
                    $amount = $list3->amount;
                    $total_amount = $total_amount+$amount;
                }
                //get total amount end

                //get plafon
                $plafon = $base_plafon - $total_amount;
                
                $result[] = array(
                    "item"=>$item,
                    "base_plafon"=>$base_plafon,
                    "usage"=>$total_amount,
                    "plafon"=>$plafon,
                    "emerge"=>$date_emerge, 
                );
            }
        }

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Success",
                "user_id"=>$user_id,
                "data"=>$result,
                "date_join"=>$date_join_raw,
            )
        );

        return response()->json($response);
        
    }


}

?>