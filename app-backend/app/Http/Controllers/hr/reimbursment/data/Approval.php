<?php

namespace App\Http\Controllers\hr\reimbursment\data;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Approval extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    function status($request_id){
        $query = DB::table('reimbursment_approval')
        ->select('reimbursment_status.*')
        ->leftjoin('reimbursment_status',"reimbursment_status.id","=","reimbursment_approval.status_id")
        ->where('request_id', $request_id)
        ->get();
        $status = array();
        $status_list = array();
        foreach($query as $list){
            $status_list[] = $list->id;
        }
        if(in_array("3", $status_list)){
            $status = 3;
        }else if(in_array("1", $status_list)){
            $status = 1;
        }else if(in_array("0", $status_list)){
            $status = 0;
        }else{
            $status = 2;
        }

        $query2 = DB::table('reimbursment_status')
        ->where('id', $status)
        ->first();

        return $query2;

    }

    function get_status($id){
        $query2 = DB::table('reimbursment_status')
        ->where('id', $id)
        ->first();

        return $query2;
    }

    function getByTransaction(Request $request, $transaction_id){
        $query = DB::table('reimbursment_request')
        ->select('id', 'status_id')
        ->where('id',$transaction_id)
        ->get();

        if(count($query) > 0){
            foreach($query as $list){

                //get status reimbursment
                $status_id = $list->status_id;
                
                if($status_id == 1 || $status_id == 4){
                    $status = $this->get_status($status_id); 
                }else{
                    $status = $this->status($list->id);
                }
            };
        }else{
            $status = DB::table('reimbursment_status')
            ->where('id', 0)
            ->first();
        }

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Success",
                "status"=>$status->id,
            )
        );

        return response()->json($response);

    }

    function destroy(Request $request){

        $params_user_id = $request->input('user_id');

        $payload = $request->json()->all();

        DB::table('approval')
        ->where('user_id', $params_user_id)
        ->where('approval', $payload['data']['id'])
        ->update(
            [
                'active' => 0,
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
            ]
        );

        $approval = DB::table('approval')
        ->where('user_id', $params_user_id)
        ->where('active', 1)
        ->get();

        if(count($approval)>0){
            $sort = 1;
            foreach($approval as $a){
                $user_approval = $a->approval;

                DB::table('approval')
                ->where('user_id', $params_user_id)
                ->where('approval', $user_approval)
                ->update(
                    [
                        'sort' => $sort,
                        'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                    ]
                );

                $sort = $sort + 1;
            }
        }

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Success",
                "payload"=>$payload,
            )
        );

        return response()->json($response);
        
    }

    function reorder(Request $request){

        $payload = $request->json()->all();

        $user_id = $payload['user_id'];

        $count = count($payload['event']);
        
        if($count > 0){
            $index = 0;
            foreach($payload['event'] as $event_list){
                $index++;
                DB::table('approval')
                ->where('user_id', $user_id)
                ->where('approval', $event_list['id'])
                ->update(
                    [
                        'sort' => $index,
                        'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                    ]
                );
            }
        }

        $query = DB::table('approval')
        ->leftjoin("users","users.id","=","approval.approval")
        ->where('user_id',$user_id)
        ->where('approval.active',1)
        ->orderBy('approval.sort', 'ASC')
        ->get();

        $approval = array();
        
        foreach($query as $list){
            $approval[] = array(
                "sort"=>$list->sort,
                "id"=>$list->approval,
                "email"=>$list->email,
            );
        };

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>$approval,
            )
        );

        return response()->json($response);
        
    }
}

?>