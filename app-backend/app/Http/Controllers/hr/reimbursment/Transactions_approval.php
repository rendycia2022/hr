<?php

namespace App\Http\Controllers\hr\reimbursment;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use App\Mail\Approval;

class Transactions_approval extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    function updateAmount($request_id){

        $query = DB::table('reimbursment_request_detail')
        ->where('request_id',$request_id)
        ->get();

        $total = 0;
        foreach($query as $list){
            $total = $total+$list->amount;
        }

        return $total;

    }

    function get(Request $request){

        $payload_id = $request->input('id');
        
        $query = DB::table('reimbursment_approval')
        ->select('reimbursment_approval.id', 
        'reimbursment_approval.status_id',
        'reimbursment_status.label', 
        'reimbursment_status.severity', 
        'reimbursment_request.id as transaction_id', 
        'reimbursment_request.request_id', 
        'reimbursment_request.user_id', 
        'reimbursment_request.created_at', 
        'employee.name')
        ->leftjoin("reimbursment_status","reimbursment_status.id","=","reimbursment_approval.status_id")
        ->leftjoin("reimbursment_request","reimbursment_request.id","=","reimbursment_approval.request_id")
        ->leftjoin("employee","employee.user_id","=","reimbursment_request.user_id")
        ->where('reimbursment_request.status_id',0)
        ->where('reimbursment_approval.approval_id',$payload_id)
        ->where('reimbursment_approval.status_id','!=',1)
        ->orderBy('reimbursment_request.created_at','DESC')
        ->get();

        $index = 0;
        $result = array();
        foreach($query as $list){

            $amount = $this->updateAmount($list->transaction_id);

            //get approval from transaction user_id
            $query2 = DB::table('approval')
            ->select('approval','sort','user_id')
            ->where('active',1)
            ->where('user_id', $list->user_id)
            ->where('approval', $payload_id)
            ->first();

            if(!empty($query2)){
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
                                $result[$index] = array(
                                    "id"=>$list->id,
                                    "status_id"=>$list->status_id,
                                    "status_label"=>$list->label,
                                    "status_severity"=>$list->severity,
                                    "transaction_id"=>$list->transaction_id,
                                    "request_id"=>$list->request_id,
                                    "name"=>$list->name,
                                    "amount"=>$amount,
                                    "created_at"=>$list->created_at,
                                    "query"=>"query4, status_id =2",
                                );
                                $index++;
                            }
                        }
                    }

                }else{
                    $result[$index] = array(
                        "id"=>$list->id,
                        "status_id"=>$list->status_id,
                        "status_label"=>$list->label,
                        "status_severity"=>$list->severity,
                        "transaction_id"=>$list->transaction_id,
                        "request_id"=>$list->request_id,
                        "name"=>$list->name,
                        "amount"=>$amount,
                        "created_at"=>$list->created_at,
                        "query"=>"query4, lainnya",
                    );
                    $index++;
                }

                $list_transaction_id[] = $list->transaction_id;
            }
            
        }

        $query5 = DB::table('reimbursment_approval')
        ->select('reimbursment_approval.id', 
        'reimbursment_approval.status_id',
        'reimbursment_status.label', 
        'reimbursment_status.severity', 
        'reimbursment_request.id as transaction_id', 
        'reimbursment_request.request_id', 
        'reimbursment_request.user_id', 
        'reimbursment_request.created_at', 
        'employee.name')
        ->leftjoin("reimbursment_status","reimbursment_status.id","=","reimbursment_approval.status_id")
        ->leftjoin("reimbursment_request","reimbursment_request.id","=","reimbursment_approval.request_id")
        ->leftjoin("employee","employee.user_id","=","reimbursment_request.user_id")
        ->where('reimbursment_approval.approval_id',$payload_id)
        ->whereIn('reimbursment_approval.status_id',['2','3','4'])
        ->whereNotIn('reimbursment_request.id',$list_transaction_id)
        ->orderBy('reimbursment_request.created_at','DESC')
        ->get();


        foreach($query5 as $list5){
            
            $amount1 = $this->updateAmount($list5->transaction_id);

            $result[$index] = array(
                "id"=>$list5->id,
                "status_id"=>$list5->status_id,
                "status_label"=>$list5->label,
                "status_severity"=>$list5->severity,
                "transaction_id"=>$list5->transaction_id,
                "request_id"=>$list5->request_id,
                "name"=>$list5->name,
                "amount"=>$amount1,
                "created_at"=>$list5->created_at,
                "query"=>"query5",
            );
            $index++;
        }

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Success",
                "data"=>$result,
            )
        );

        return response()->json($response);

    }

    function store(Request $request){

        $payload = $request->json()->all();

        if($payload['status']){
            $status_id = 2; //true = Approved

            //sending email
            $this->email_blast($payload['transaction_id'], $payload['user_local']);


        }else{
            $status_id = 3; //false = Rejected

            DB::table('reimbursment_request')->where('id', $payload['transaction_id'])->update(
                [
                    'status_id' =>$status_id,
                    'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                ]
            );
        }

        DB::table('reimbursment_approval')->where('id', $payload['id'])->update(
            [
                'status_id' =>$status_id,
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
            ]
        );

        // status builder start
        $statusQuery = DB::table('reimbursment_status')
        ->where('id',$status_id)
        ->first();

        $status_label = $statusQuery->label;
        $status_severity = $statusQuery->severity;

        // status builder end
        

        $result = array(
            "status_id"=>$status_id,
            "status_label"=>$status_label,
            "status_severity"=>$status_severity,
            
            "id"=>$payload['id'],
            "request_id"=>$payload['request_id'],
            "name"=>$payload['name'],
            "amount"=>$payload['amount'],
            "created_at"=>$payload['created_at'],
        );

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "data"=>$result,
            )
        );

        return response()->json($response);
        
    }

    function email_blast($transaction_id, $user_approval){
        
        $reimbursement_request = DB::table('reimbursment_request')
        ->select('reimbursment_request.user_id')
        ->where('reimbursment_request.id', $transaction_id)
        ->first();

        // get name of user request transaction
        $user_request = DB::table('employee')
        ->select('name')
        ->where('user_id',$reimbursement_request->user_id)
        ->first();

        // search user next approval user
        $next_approval = DB::table('approval')
        ->select('user_id','approval','sort')
        ->where('user_id', $reimbursement_request->user_id)
        ->where('approval', $user_approval)
        ->where('active', 1)
        ->first();

        $sort = $next_approval->sort+1;

        $approval = DB::table('approval')
        ->select('users.email','employee.name')
        ->leftjoin('users','approval.approval', '=', 'users.id')
        ->leftjoin('employee','approval.approval', '=', 'employee.user_id')
        ->where('approval.user_id',$reimbursement_request->user_id)
        ->where('approval.active',1)
        ->where('approval.sort',$sort)
        ->first();

        if(isset($approval)){

            $detail = $this->getById($transaction_id);

            $details = [
                'user_approval' => $approval->name,
                'user_request' => $user_request->name,
                'date_created' => $detail['created_at'],
                'request_id' => $detail['request_id'],
                'total_amount' => 'Rp. '.number_format($detail['amount'],2,'.',','),
                'plafon_item' => $detail['item_name'],
            ];

            

            try {
                // code 
                // if something is not as expected 
                    // throw exception using the "throw" keyword 
                // code, it won't be executed if the above exception is thrown 
                Mail::to($approval->email)->send(new Approval($details));

            } catch (Exception $e) {
                // exception is raised and it'll be handled here 
                // $e->getMessage() contains the error message 
            }
            
        }

    }

    function getById($id){

        $query = DB::table('reimbursment_request')
        ->select('reimbursment_request.*', 'plafon.id as plafon_id', 'plafon.item as plafon_item')
        ->leftjoin("plafon","plafon.id","=","reimbursment_request.plafon_id")
        ->where('reimbursment_request.id',$id)
        ->orderBy('created_at','DESC')
        ->get();

        $result = array();

        foreach($query as $list){

            //get status reimbursment
            $status_id = $list->status_id;
            
            if($status_id == 1 || $status_id == 4){
                $status = $this->get_status($status_id); 
            }else{
                $status = $this->status($list->id);
            }

            //get amount
            $amount = $this->updateAmount($list->id);

            $created_date = date_create($list->created_at);
            $result = array(
                "id"=>$list->id,
                "request_id"=>$list->request_id,
                "plafon_id"=>$list->plafon_id,
                "amount"=>$amount,
                "created_at"=>date_format($created_date, 'Y-m-d'),
                "items"=>array(
                    "id"=>$list->plafon_id,
                    "code"=>$list->plafon_id,
                    "name"=>$list->plafon_item,
                ),
                "item_name"=>$list->plafon_item,
                "status"=>$status,
            );
        };

        return $result;

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

    function storeBurst(Request $request){

        $payload = $request->json()->all();
        $count = count($payload['data']);
        for($i=0; $i<$count; $i++){
            $id[] = $payload['data'][$i]['id'];
        };

        DB::table('users')->whereIn('id', $id)->update(
            [
                'active' =>0,
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
            ]
        );

        DB::table('approval')->whereIn('user_id', $id)->update(
            [
                'active' =>0,
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
            ]
        );

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