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

class Transactions extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    function transaction_id($user_id){
        $query = DB::table('reimbursment_request')
        ->where('user_id',$user_id)
        ->get();

        $count = count($query);

        if($count < 1){
            $no = '001';
        }else{
            $increment = $count+1;
            if($count > 99){
                $no = $increment;
            }elseif($count > 9){
                $no = '0'.$increment;
            }else{
                $no = '00'.$increment;
            }
        }

        $year = gmdate('Y', time()+(60*60*7));
        $month = gmdate('m', time()+(60*60*7));

        //Invoice No. Result
        $noTransaction = $year.$month.$no;

        return $noTransaction;
    }

    function getId(Request $request){

        $payload_id = $request->input('id');

        $transaction = $this->transaction_id($payload_id);

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Login success",
                "data"=>array(
                    "request_id"=>$transaction,
                    "id"=>Uuid::uuid4(),
                ),
            )
        );

        return response()->json($response);
        
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

    function get(Request $request){

        $payload_id = $request->input('id');

        $query = DB::table('reimbursment_request')
        ->select('reimbursment_request.*', 'plafon.id as plafon_id', 'plafon.item as plafon_item')
        ->leftjoin("plafon","plafon.id","=","reimbursment_request.plafon_id")
        ->where('user_id',$payload_id)
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
            $result[] = array(
                "id"=>$list->id,
                "request_id"=>$list->request_id,
                "plafon_id"=>$list->plafon_id,
                "amount"=>$amount,
                "bank"=>array(
                    "account"=>$list->bank_account,
                    "rekening"=>$list->bank_rekening,
                    "name"=>$list->bank_name,
                ),
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
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Login success",
                "data"=>$result,
            )
        );

        return response()->json($response);
        
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

    function store(Request $request){

        $payload = $request->json()->all();

        $query = DB::table('reimbursment_request')
        ->where('id',$payload['id'])
        ->first();

       if(!isset($query)){
            $request_id = $this->transaction_id($payload['user_local']);

            // insert reimbursment approval
            $query1 = DB::table('approval')
            ->where('active',1)
            ->where('user_id',$payload['user_local'])
            ->orderby('sort', 'ASC')
            ->get();

            $count_approval = count($query1);

            if($count_approval > 0){ //check approval list
                // bank account
                $bank = $payload['bank'];

                // insert users
                DB::table('reimbursment_request')->insert(
                    [
                        'id'=>$payload['id'],
                        'request_id'=>$request_id,
                        'user_id'=>$payload['user_local'],
                        'plafon_id' => $payload['items']['id'],
                        'created_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                        'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                        'bank_account'=>strtoupper($bank['account']),
                        'bank_rekening'=>$bank['rekening'],
                        'bank_name'=>$bank['name'],
                    ]
                );
                
                foreach($query1 as $list1){
                    DB::table('reimbursment_approval')->insert(
                        [
                            'id'=>Uuid::uuid4(),
                            'request_id'=>$payload['id'],
                            'approval_id'=>$list1->approval,
                            'created_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                            'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                            'sort'=>$list1->sort,
                        ]
                    );
                }
                
                //sending email
                $this->email_blast($payload['id']);

                $status = 200;
                $stored = 'created';
                
            }else{
                $status = 404;
                $stored = "Approval not exist";
            }
        }else{
            DB::table('reimbursment_request')
            ->where('id', $payload['id'])->update(
                [
                    'plafon_id' => $payload['items']['id'],
                    'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                ]
            );
            $status = 200;
            $stored = 'updated';
        }
        
        
        $data = $this->getById($payload['id']);

        $response = array(
            "status"=>$status,
            "metadata"=>array(
                "message"=>"success",
                "stored"=>$stored,
                "data"=>$data,
            )
        );

        return response()->json($response);
        
    }

    function email_blast($transaction_id){
        
        $reimbursement_request = DB::table('reimbursment_request')
        ->select('reimbursment_request.user_id')
        ->where('reimbursment_request.id', $transaction_id)
        ->first();

        // get name of user request transaction
        $user_request = DB::table('employee')
        ->select('name')
        ->where('user_id',$reimbursement_request->user_id)
        ->first();

        $approval = DB::table('approval')
        ->select('users.email','employee.name')
        ->leftjoin('users','approval.approval', '=', 'users.id')
        ->leftjoin('employee','approval.approval', '=', 'employee.user_id')
        ->where('approval.user_id',$reimbursement_request->user_id)
        ->where('approval.active',1)
        ->orderby('approval.sort', 'ASC')
        ->first();

        $detail = $this->getById($transaction_id);

        $details = [
            'user_approval' => $approval->name,
            'user_request' => $user_request->name,
            'date_created' => $detail['created_at'],
            'request_id' => $detail['request_id'],
            'total_amount' => 'Rp. '.number_format($detail['amount'],2,'.',','),
            'plafon_item' => $detail['item_name'],
        ];

        Mail::to($approval->email)->send(new Approval($details));

    }

    function clear_data(Request $request){

        $payload_transaction_id = $request->input('transaction_id');

        $reimbursment_request = DB::table('reimbursment_request')->where('id', $payload_transaction_id)->first();

        $message = 'Nothing changed';

        if(!isset($reimbursment_request)){
            // check request detail
            $reimbursment_request_detail = DB::table('reimbursment_request_detail')->where('request_id', $payload_transaction_id)->get();
            if(isset($reimbursment_request_detail)){
                DB::table('reimbursment_request_detail')->where('request_id', $payload_transaction_id)->delete(); //delete if data exists

                foreach($reimbursment_request_detail as $rrd_list){
                    $reimbursment_files = DB::table('reimbursment_files')->where('request_detail_id', $rrd_list->id)->get();
                    if(isset($reimbursment_files)){
                        DB::table('reimbursment_files')->where('request_detail_id', $rrd_list->id)->delete();

                        //delete folder upload
                        $path = base_path('storage/files_upload/'.$payload_transaction_id);
                        File::deleteDirectory($path);
                    }       
                };
            }    
            
            $message = 'Data deleted';
        }

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>$message,
            )
        );

        return response()->json($response);
    }

    function destroy(Request $request){

        $payload = $request->json()->all();

        DB::table('reimbursment_request')
            ->where('id', $payload['data']['id'])->update(
                [
                    'status_id' => 1,
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
}

?>