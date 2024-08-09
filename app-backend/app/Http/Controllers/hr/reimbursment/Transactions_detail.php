<?php

namespace App\Http\Controllers\hr\reimbursment;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\File;

class Transactions_detail extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    function getId(Request $request){

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Success",
                "data"=>array(
                    "id"=>Uuid::uuid4(),
                ),
            )
        );

        return response()->json($response);
        
    }

    function get(Request $request){

        $payload_request_id = $request->input('request_id');
        $payload_plafon_id = $request->input('plafon_id');

        $query = DB::table('reimbursment_request_detail')
        ->where('request_id',$payload_request_id)
        ->where('plafon_id',$payload_plafon_id)
        ->get();

        $result = array();

        foreach($query as $list){
            // get files data
            $query2 = DB::table('reimbursment_files')
            ->where('request_detail_id',$list->id)
            ->get();
            $files = array();
            foreach($query2 as $list2){
                $files[] = $list2->path.$list2->file_name;
            }

            $result[] = array(
                "id"=>$list->id,
                "description"=>$list->description,
                "treatment"=>$list->treatment,
                "amount"=>$list->amount,
                "request_id"=>$list->request_id,
                "created_at"=>$list->created_at,
                "plafon_id"=>$list->plafon_id,
                "effective_date"=>$list->effective_date,
                "files"=>$files,
            );
        }
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Success",
                "data"=>$result,
                "request_id"=>$payload_request_id,
            )
        );

        return response()->json($response);
        
    }

    function getPlafon(Request $request){
        $payload_item = $request->input('item');

        $query = DB::table('plafon')
        ->where('id',$payload_item)
        ->first();

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Success",
                "item"=>$payload_item,
                "data"=>$query,
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

    function plafon_validation($request_id,$plafon_id,$user_id){
        
        // get plafon from job in employee
        $query2 = DB::table('plafon')
        ->where('id', $plafon_id)
        ->get();

        $year_now = gmdate('Y', time()+(60*60*7));

        // build data plafon
        foreach($query2 as $list2){
            $base_plafon = $list2->amount;

            //get total amount start
            $query3 = DB::table('reimbursment_request')
            ->select('reimbursment_request_detail.amount')
            ->leftjoin("reimbursment_request_detail","reimbursment_request_detail.request_id","=","reimbursment_request.id")
            ->where('reimbursment_request.status_id', 4)
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

            //new transaction amount start
            $query4 =  DB::table('reimbursment_request_detail')
            ->select('reimbursment_request_detail.amount')
            ->where('reimbursment_request_detail.request_id', $request_id)
            ->where('reimbursment_request_detail.plafon_id', $plafon_id)
            ->get();

            $total_amount_new_transaction = 0;
            if(isset($query4)){
                foreach($query4 as $list4){
                    $total_amount_new_transaction = $total_amount_new_transaction + $list4->amount;
                }
            }
            //new transaction amount end

            //transaction past start
            $total_amount_transaction = 0;

            $query5 = DB::table('reimbursment_request')
            ->select('id')
            ->where('status_id', 0)
            ->where('user_id', $user_id)
            ->where('plafon_id', $plafon_id)
            ->where('id','!=',$request_id)
            ->WhereYear('created_at',$year_now)
            ->get();

            if(isset($query5)){
                foreach($query5 as $list5){
                    $reimbursment_request_id = $list5->id;

                    // get status from approval
                    $query6 = DB::table('reimbursment_approval')
                    ->select('status_id')
                    ->where('request_id', $reimbursment_request_id)
                    ->get();
                    $status_list = array();
                    //collect every status in variable
                    foreach($query6 as $list6){
                        $status_list[] = $list6->status_id;
                    }
                    if(empty($status_list) || in_array("3", $status_list)){
                        continue;
                    }else{
                        // if variable status_list doesn't have status 3 in reimbursment_approval
                        // get amount from request_id in reimbursment_request_detail
                        $query7 = DB::table('reimbursment_request_detail')
                        ->select('amount')
                        ->where('request_id', $reimbursment_request_id)
                        ->get();

                        foreach($query7 as $list7){
                            $transaction_amount = $list7->amount;

                            $total_amount_transaction = $total_amount_transaction + $transaction_amount;

                        }

                    }

                }
            }

            //transaction past end
        }

        //get plafon
        $plafon = $base_plafon - ($total_amount + $total_amount_new_transaction + $total_amount_transaction);

        return $plafon;
    }

    function getPlafonById(Request $request, $request_id, $plafon_id){

        $user_id = $request->input('id');

        $result = $this->plafon_validation($request_id, $plafon_id, $user_id);

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Success",
                "data"=>$result,
            )
        );

        return response()->json($response);
    }

    function getById($id){
        $query = DB::table('reimbursment_request_detail')
        ->where('id',$id)
        ->get();

        $result = array();

        foreach($query as $list){
            // get files data
            $query2 = DB::table('reimbursment_files')
            ->where('request_detail_id',$list->id)
            ->get();
            $files = array();
            foreach($query2 as $list2){
                $files[] = $list2->path.$list2->file_name;
            }

            $result = array(
                "id"=>$list->id,
                "description"=>$list->description,
                "treatment"=>$list->treatment,
                "amount"=>$list->amount,
                "request_id"=>$list->request_id,
                "created_at"=>$list->created_at,
                "plafon_id"=>$list->plafon_id,
                "effective_date"=>$list->effective_date,
                "files"=>$files,
            );
        }

        return $result;
    }

    function store(Request $request){

        $payload = $request->json()->all();

        $plafon_validation = $this->plafon_validation($payload['request_id'],$payload['plafon_id'],$payload['user_local']);

        //validation plafon limit
        if($payload['amount'] <= $plafon_validation){
            // insert files data start

            // create base directory
            $path = base_path('public/files/'.$payload['request_id'].'/'.$payload['id'].'/'); //path moving files
            $path_stored = env('APP_HOST').'files/'.$payload['request_id'].'/'.$payload['id'].'/';
            if (!file_exists($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }

            $count_file = count($payload['files']);
            // checking file exists in public folder
            $target_folder_root = 'upload/batch/';
            $filePath = base_path('public/'.$target_folder_root.$payload['id'].'/');

            for($i=0; $i<$count_file; $i++){
                $file_name = $payload['files'][$i];

                // moving file
                if(file_exists($path)){
                    if(file_exists($filePath.$file_name)){
                        File::move($filePath.$file_name, $path.$file_name);
                    }
                }    

                // insert to database
                DB::table('reimbursment_files')->insert(
                    [
                        'id'=>Uuid::uuid4(),
                        'file_name'=>$file_name,
                        'path'=>$path_stored,
                        'request_detail_id'=>$payload['id'],
                        'created_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                    ]
                );
            }

            //deleting folder public
            if(file_exists($filePath)){
                File::deleteDirectory($filePath);
            }

            // insert detail data
            DB::table('reimbursment_request_detail')->insert(
                [
                    'id'=>$payload['id'],
                    'description'=>$payload['description'],
                    'treatment'=>$payload['treatment'],
                    'amount'=>$payload['amount'],
                    'request_id'=>$payload['request_id'],
                    'created_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                    'plafon_id' => $payload['plafon_id'],
                    'effective_date' => $payload['effective_date'],
                    'user_id' => $payload['user_local'],
                ]
            );
            // insert files data end

            $status = 200;
            $message = 'Success';
        
        }else{

            $status = 400;
            $message = 'Failed';
        }

        
        
        $data_updated = $this->getById($payload['id']);
        

        $response = array(
            "status"=>$status,
            "metadata"=>array(
                "message"=>$message,
                "data"=>$data_updated,
                "plafon_validation"=>$plafon_validation,
            )
        );

        return response()->json($response);
        
    }

    function destroy(Request $request){

        $payload = $request->json()->all();

        DB::table('reimbursment_request_detail')->where('id', $payload['data']['id'])->delete();

        // check files exists
         $reimbursment_files = DB::table('reimbursment_files')->where('request_detail_id', $payload['data']['id'])->first();
         if($reimbursment_files){
            DB::table('reimbursment_files')->where('request_detail_id', $payload['data']['id'])->delete(); //delete if data exists

            //delete folder upload
            $path = base_path('storage/files_upload/'.$payload['data']['request_id'].'/'.$payload['data']['id']);
            File::deleteDirectory($path, true);
         }

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "payload"=>$payload,
            )
        );

        return response()->json($response);
        
    }
}

?>