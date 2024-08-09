<?php

namespace App\Http\Controllers\hr\reimbursment\data;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Transaction_detail extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    function get(Request $request, $id){

        $query = DB::table('reimbursment_request_detail')
        ->select(
        'reimbursment_request_detail.id', 
        'reimbursment_request_detail.description',
        'reimbursment_request_detail.treatment',
        'reimbursment_request_detail.amount',
        'reimbursment_request_detail.effective_date',
        'reimbursment_request_detail.plafon_id',
        'reimbursment_request.user_id',
        )
        ->leftjoin("reimbursment_request","reimbursment_request.id","=","reimbursment_request_detail.request_id")
        ->where('reimbursment_request_detail.request_id',$id)
        ->get();

        $result = array();
        $total_amount = 0;
        foreach($query as $list){

            //total amount
            $total_amount = $total_amount+$list->amount;
            
            // plafon
            $query2 = DB::table('plafon')
                ->where('id',$list->plafon_id)
                ->first();

            //files
            $files = array();
            $query3 = DB::table('reimbursment_files')
                ->where('request_detail_id',$list->id)
                ->get();
            
            foreach($query3 as $list3){
                $files[] = $list3->path.$list3->file_name;
            };

            $result[] = array(
                "id"=>$list->id,
                "description"=>$list->description,
                "amount"=>$list->amount,
                "effective_date"=>$list->effective_date,
                "plafon_id"=>$list->plafon_id,
                
                "plafon_item"=>$query2->item,

                "files"=>$files,
            );
        }

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Success",
                "data"=>$result,
                "total_amount"=>$total_amount, 
            )
        );

        return response()->json($response);
        
    }

    function getByUserAndPlafon(Request $request, $user_id, $plafon_id){

        $query = DB::table('reimbursment_request')
        ->select(
            'reimbursment_request.id', 
            'reimbursment_request.request_id', 
            'reimbursment_request.plafon_id', 
            'reimbursment_request.user_id', 
            'reimbursment_request_detail.id as detail_id',
            'reimbursment_request_detail.amount',
            'reimbursment_request_detail.description',
            'reimbursment_request_detail.effective_date',
        )
        ->leftjoin("reimbursment_request_detail","reimbursment_request_detail.request_id","=","reimbursment_request.id")
        ->where('reimbursment_request.user_id',$user_id)
        ->where('reimbursment_request.plafon_id',$plafon_id)
        ->where('reimbursment_request.status_id',4)
        ->get();

        $result = array();

        foreach($query as $list){
            // get files data
            $query2 = DB::table('reimbursment_files')
            ->where('request_detail_id',$list->detail_id)
            ->get();
            $files = array();
            foreach($query2 as $list2){
                $files[] = $list2->path.$list2->file_name;
            }

            $result[] = array(
                "id"=>$list->id,
                "request_id"=>$list->request_id,
                "plafon_id"=>$list->plafon_id,
                "user_id"=>$list->user_id,
                "description"=>$list->description,
                "amount"=>$list->amount,
                "request_id"=>$list->request_id,
                "effective_date"=>$list->effective_date,
            );
        }

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "user_id"=>$user_id,
                "plafon_id"=>$plafon_id,
                "data"=>$result,
            )
        );

        return response()->json($response);
        
    }
}

?>