<?php

namespace App\Http\Controllers\hr\reimbursment\data;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\reimbursement\DashboardModel;
use App\Models\reimbursement\TransactionModel;
use App\Models\reimbursement\ReportModel;

class Payment extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->DashboardModel = new DashboardModel;
        $this->TransactionModel = new TransactionModel;
        $this->ReportModel = new ReportModel;
    }

    function summary(Request $request){

        $payload = $request->json()->all();

        $timestamp = gmdate('Y-m-d H:i:s', time()+(60*60*7));
        $periode = gmdate('F Y', time()+(60*60*7));
        $week = gmdate('W', time()+(60*60*7));

        $data = $payload['data'];
        $count_data = count($data);

        $summary = array();
        for($i=0; $i<$count_data; $i++){
            $status_id = $data[$i]['status_id'];

            // checking status id == 2 / approved;
            if($status_id == 2){
                // get summary
                $id = $data[$i]['id'];

                $getData = $this->ReportModel->getById($id);

                $summary[] = $getData;
            }
        }

        $getCompany = $this->ReportModel->getCompany();
        foreach($getCompany as $gc){
            $company_id = $gc->company_id;
            $company_name = $gc->company_name;
            $company_code = $gc->company_code;

            $count_summary = count($summary);
            for($s=0; $s<$count_summary; $s++){
                $summary_company_id = $summary[$s]['report']['company_id'];

                if($summary_company_id == $company_id){
                    $form[$company_id]['item'][] = $summary[$s]['report'];
                }
            }

            if(isset($form[$company_id])){
                
                $logo = base_path('public/company/'.$company_code.'.png');
                $form[$company_id]['header'] = array(
                    'logo'=>$logo,
                    'title'=>$company_name,
                    'subtitle'=>'Medical Claim Report',
                    'periode'=>$periode,
                    'week'=>$week,
                    
                    // sign
                    'sign_1'=>'Muhidin',
                    'sign_2'=>'Hudan',
                    'sign_3'=>'Elni',
                );
            }
        }

        $form = array_values($form);
        
        $create = [
            'form'=>$form,
            
        ];

        $pdf = Pdf::loadView('pdf.summary', $create);
        return $pdf->download('sample.pdf');

        // return response()->json($form);
    }

    function get(Request $request){

        $query = DB::table('reimbursment_request')
        ->select('reimbursment_request.id', 
        'reimbursment_request.request_id', 
        'reimbursment_request.created_at',
        'reimbursment_request.status_id',
        'employee.name',
        )
        ->leftjoin("employee","employee.user_id","=","reimbursment_request.user_id")
        ->where('reimbursment_request.status_id', 0)
        ->orWhere('reimbursment_request.status_id', 4)
        ->orderBy('reimbursment_request.created_at','DESC')
        ->get();

        $result = array();
        foreach($query as $list){
            $request_id = $list->id;

            $amount = $this->updateAmount($request_id);

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

                $status_id = $list->status_id;

                if($status_id == 1 || $status_id == 4){
                    $status = $this->get_status($status_id);
                }else{
                    $status = $this->status($request_id);
                }

                $result[] = array(
                    "id"=>$list->id,
                    "transaction_id"=>$list->id,
                    "request_id"=>$list->request_id,
                    "created_at"=>$list->created_at,
                    "amount"=>$amount,
                    "name"=>$list->name,
                    "status_id"=>$status->id,
                    "status_label"=>$status->label,
                    "status_severity"=>$status->severity,
                );
             }
    
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

    function paid(Request $request, $id){

        DB::table('reimbursment_request')
        ->where('id', $id)->update(
            [
                'status_id' => 4,
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
            ]
        );
        
        $result = $this->get_dataById($id);

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Success",
                "data"=>$result,
                "id"=>$id,
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

    function get_dataById($id){
        $query = DB::table('reimbursment_request')
        ->select('reimbursment_request.id', 
        'reimbursment_request.request_id', 
        'reimbursment_request.created_at',
        'reimbursment_request.status_id',
        'employee.name',
        )
        ->leftjoin("employee","employee.user_id","=","reimbursment_request.user_id")
        ->where('reimbursment_request.id', $id)
        ->get();

        $result = array();
        foreach($query as $list){
            $request_id = $list->id;

            $amount = $this->updateAmount($request_id);

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

                $status_id = $list->status_id;

                if($status_id == 1 || $status_id == 4){
                    $status = $this->get_status($status_id);
                }else{
                    $status = $this->status($request_id);
                }

                $result = array(
                    "id"=>$list->id,
                    "transaction_id"=>$list->id,
                    "request_id"=>$list->request_id,
                    "created_at"=>$list->created_at,
                    "amount"=>$amount,
                    "name"=>$list->name,
                    "status_id"=>$status->id,
                    "status_label"=>$status->label,
                    "status_severity"=>$status->severity,
                );
             }
    
        }

        return $result;
    }

    function getPDF(Request $request, $id){

        $reimbursment_request = DB::table('reimbursment_request')
        ->select('reimbursment_request.id', 
        'reimbursment_request.request_id', 
        'reimbursment_request.created_at',
        'reimbursment_request.status_id',
        'employee.name',
        'employee.division',
        'company.name as company_name',
        )
        ->join("employee","employee.user_id","=","reimbursment_request.user_id")
        ->join("company","company.id","=","employee.company_id")
        ->where('reimbursment_request.id', $id)
        ->get();

        // header
        $header_document_number = '';
        $header_division = '';
        $header_division_name = '';
        $header_company = '';
        $header_date_display = '';

        // bank
        $bank_account = '';

        if(count($reimbursment_request)>0){
            foreach($reimbursment_request as $rr){

                $header_document_number = $rr->request_id;
                $header_division = $rr->division;
                $header_division_name = $rr->division;
                $header_company = $rr->company_name;
                $header_date = $rr->created_at;
                
                $bank_account = $rr->name;

                // date for sign
                $header_date_display = date_create($header_date);
                $header_date_display = date_format($header_date_display,"d F Y");
            }
        }

        // items
        $items_list = array();
        $reimbursment_request_detail = DB::table('reimbursment_request_detail')
        ->select(
            'reimbursment_request_detail.id', 
            'reimbursment_request_detail.description',
            'reimbursment_request_detail.treatment',
            'reimbursment_request_detail.amount',
            'reimbursment_request_detail.effective_date',
            'reimbursment_request_detail.plafon_id',
            'reimbursment_request.user_id',

            'plafon.item',
        )
        ->join("reimbursment_request","reimbursment_request.id","=","reimbursment_request_detail.request_id")
        ->join("plafon","plafon.id","=","reimbursment_request_detail.plafon_id")
        ->where('reimbursment_request_detail.request_id',$id)
        ->get();

        if(count($reimbursment_request_detail)>0){
            foreach($reimbursment_request_detail as $rrd){
                
                $item = $rrd->item;
                $description = $rrd->description;
                $treatment = $rrd->treatment;
                $effective_date = $rrd->effective_date;

                $amount = $rrd->amount;

                $remarksBuilder = "Plafon: ".$item;
                $remarksBuilder .= "<br/>"."Desc: ".$description." - ".$treatment;
                $remarksBuilder .= "<br/>"."Effective date: ".$effective_date;

                $remarks = $remarksBuilder;

                $items_list[] = array(
                    "remarks"=>$remarks,
                    "amount"=>$amount,
                );
                
            }
        }

        // echo "<pre>";
        //     print_r($items_list);
        //     echo "</pre>";die;
        

        $data = [
            // header
            'header_document_number'=>$header_document_number,
            'header_division'=>$header_division,
            'header_division_name'=>$header_division_name,
            'header_company'=>$header_company,
            'header_date'=>$header_date_display,

            

            // bank
            'bank_account'=>$bank_account,

            // items
            'items_list'=>$items_list,
            
            // sign
            'date_sign'=>$header_date_display,
        ];

        // return $data;

        $pdf = Pdf::loadView('pdf.af_document', $data);
        return $pdf->stream('Document AF.pdf');

    }

    
}

?>