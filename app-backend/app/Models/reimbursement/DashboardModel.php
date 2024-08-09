<?php

namespace App\Models\reimbursement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DashboardModel extends Model
{

    public function __construct(){
    }

    public function limit_plafon($transaction_id,$user_id, $plafon_id, $created_at){

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
        ->where('plafon.id', $plafon_id)
        ->where('plafon.job_level', $query->job)
        ->where('active',1)
        ->get();

        $result = array();

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
                ->whereIn('reimbursment_request.status_id', [4,0])
                ->where('reimbursment_request.id','!=',$transaction_id)
                ->where('reimbursment_request.created_at','<',$created_at)
                ->where('reimbursment_request.user_id', $user_id)
                ->where('reimbursment_request.plafon_id', $plafon_id)
                ->WhereYear('reimbursment_request.created_at',$year_now)
                ->get();

                $total_amount = 0;
                
                foreach($query3 as $list3){
                    $amount = $list3->amount;
                    $total_amount = $total_amount+$amount;
                }
                //get total amount end

                //get plafon
                $plafon = $base_plafon - $total_amount;
                
                $result = array(
                    "item"=>$item,
                    "base_plafon"=>$base_plafon,
                    "usage"=>$total_amount,
                    "plafon"=>$plafon,
                    "emerge"=>$date_emerge, 
                );
            }
        }

        return $result;
        
    }

    public function limit_plafon_detail($transaction_detail_id,$user_id, $plafon_id, $created_at){

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
        ->leftjoin('plafon_setting','plafon.id','=','plafon_setting.plafon_id')
        ->whereDate('plafon_setting.effective_date','<=',$today)
        ->where('plafon.id', $plafon_id)
        ->where('plafon.job_level', $query->job)
        ->where('active',1)
        ->get();

        $result = array();

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

                $year_now_new = date_create($created_at);
                $year_now = date_format($year_now_new,"Y");


                //get total amount start
                $query3 = DB::table('reimbursment_request')
                ->select('reimbursment_request_detail.amount')
                ->leftjoin("reimbursment_request_detail","reimbursment_request_detail.request_id","=","reimbursment_request.id")
                ->whereIn('reimbursment_request.status_id', [4])
                ->where('reimbursment_request_detail.id','!=',$transaction_detail_id)
                ->where('reimbursment_request_detail.created_at','<',$created_at)
                ->where('reimbursment_request.user_id', $user_id)
                ->where('reimbursment_request.plafon_id', $plafon_id)
                ->WhereYear('reimbursment_request.created_at',$year_now)
                ->get();

                $total_amount = 0;
                
                foreach($query3 as $list3){
                    $amount = $list3->amount;
                    $total_amount = $total_amount+$amount;
                }
                //get total amount end

                //get plafon
                $plafon = $base_plafon - $total_amount;
                
                $result = array(
                    "item"=>$item,
                    "base_plafon"=>$base_plafon,
                    "usage"=>$total_amount,
                    "plafon"=>$plafon,
                    "emerge"=>$date_emerge, 
                );
            }
        }

        return $result;
        
    }

}