<?php

namespace App\Http\Controllers\hr\reimbursment\data;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\reimbursement\DashboardModel;
use App\Models\reimbursement\TransactionModel;

class Transaction extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->DashboardModel = new DashboardModel;
        $this->TransactionModel = new TransactionModel;
    }

    function getById(Request $request, $id){

        $query = DB::table('reimbursment_request')
        ->select(
            'reimbursment_request.id',
            'reimbursment_request.request_id',
            'reimbursment_request.user_id',
            'reimbursment_request.plafon_id',
            'reimbursment_request.status_id',
            'reimbursment_request.created_at',
            'plafon.item as plafon_item',
            'plafon.amount as plafon_amount',
            'jobs.level',
            'jobs.grade',
            'employee.name as employee_name',
        )
        ->leftjoin("plafon","reimbursment_request.plafon_id","=","plafon.id")
        ->leftjoin("jobs","plafon.job_level","=","jobs.id")
        ->leftjoin("employee","reimbursment_request.user_id","=","employee.user_id")
        ->where('reimbursment_request.id',$id)
        ->first();

        $total_amount = $this->updateAmount($id);

        $limit_plafon = $this->DashboardModel->limit_plafon($query->id,$query->user_id, $query->plafon_id,$query->created_at);
        $usage = $limit_plafon['usage'];

        // balance
        $balance = $limit_plafon['base_plafon'] - ($total_amount+$usage);

        $status_id = $query->status_id;
            
        if($status_id == 1 || $status_id == 4){
            $status = $this->get_status($status_id); 
        }else{
            $status = $this->status($id);
        }

        $approval = $this->approval($id); 

        $result = array(
            "id"=>$query->id,
            "request_id"=>$query->request_id,
            "level"=>$query->level,
            "grade"=>$query->grade,
            "status_id"=>$status,
            "created_at"=>$query->created_at,
            "total_amount"=>$total_amount,
            "plafon_item"=>$query->plafon_item,
            "plafon_amount"=>$query->plafon_amount,
            "usage"=>$usage,
            "balance"=>$balance,

            "employee_name"=>$query->employee_name,

            "approval"=>$approval
            
        );

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Success",
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

    function approval($request_id){
        $query = DB::table('reimbursment_approval')
        ->select('reimbursment_approval.approval_id')
        ->where('request_id', $request_id)
        ->get();

        $approval_list = array();

        foreach($query as $list){
            $approval_id = $list->approval_id;

            $query2 = DB::table('employee')
            ->select('employee.name')
            ->where('user_id', $approval_id)
            ->get();

            foreach($query2 as $list2){
                $approval_list[] = array(
                    "name"=>$list2->name,
                );
            }
        }

        return $approval_list;
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

    function limitPlafon($request_id, $user_id, $plafon_id){

        // get plafon from job in employee
        $query2 = DB::table('plafon')
        ->where('id', $plafon_id)
        ->get();

        // build data plafon
        foreach($query2 as $list2){

            $base_plafon = $list2->amount;
            $plafon_id = $list2->id;

            //get total amount start
            $query3 = DB::table('reimbursment_request')
            ->select('reimbursment_request_detail.amount')
            ->leftjoin("reimbursment_request_detail","reimbursment_request_detail.request_id","=","reimbursment_request.id")
            ->where('reimbursment_request.status_id', 4)
            ->where('reimbursment_request.user_id', $user_id)
            ->where('reimbursment_request.plafon_id', $plafon_id)
            ->get();

            $total_amount = 0;
            
            foreach($query3 as $list3){
                $amount = $list3->amount;
                $total_amount = $total_amount+$amount;
            }
            //get total amount end

            //get new request detail start

            // $query4 = DB::table('reimbursment_request_detail')
            // ->select('amount')
            // ->where('request_id', $request_id)
            // ->get();

            // $total_new_amount = 0;
            // foreach($query4 as $list4){
            //     $new_amount = $list4->amount;
            //     $total_new_amount = $total_new_amount + $new_amount;
            // }

            //get new request detail end

            //get plafon
            // $limit_plafon = $base_plafon - ($total_amount + $total_new_amount);

        }

        return $total_amount;
    }

    function getForReport(Request $request){

        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        
        $data = $this->generated_data($start_date,$end_date);

        if(isset($data['data'])){
            $data['data'] = array_values($data['data']); //reindexing for frontend
        }else{
            $data['data'] = array();
        }
        

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"Success",
                "report"=>$data['data'],
                "start_date"=>$start_date,
                "end_date"=>$end_date,
            )
        );

        return response()->json($response);
    }

    function generated_data($start_date,$end_date){
        $query = DB::table('reimbursment_request_detail')
        ->select(
            'reimbursment_request_detail.effective_date',
            'reimbursment_request.id',
            'reimbursment_request.request_id',
            'reimbursment_request.created_at',
            'employee.emp_id',
            'employee.name',
            'employee.division',
            'plafon.id as plafon_id',
            'plafon.item',
            'reimbursment_request_detail.id as transaction_detail_id',
            'reimbursment_request_detail.user_id',
            'reimbursment_request_detail.description',
            'reimbursment_request_detail.treatment',
            'reimbursment_request_detail.amount',
            'reimbursment_request_detail.created_at as detail_created',
            'company.name as company_name',
        )
        ->leftjoin("reimbursment_request","reimbursment_request.id","=","reimbursment_request_detail.request_id")
        ->leftjoin("plafon","plafon.id","=","reimbursment_request_detail.plafon_id")
        ->leftjoin("employee","employee.user_id","=","reimbursment_request_detail.user_id")
        ->leftjoin("company","employee.company_id","=","company.id")
        ->where('reimbursment_request.status_id', '!=', 1)
        ->where('reimbursment_request.user_id', '!=', '8ddcfaf8-865e-46b9-9421-fc6d8b933be2');
        if(isset($start_date)){
            $query = $query->whereDate('reimbursment_request.created_at','>=',$start_date);
        }
        if(isset($end_date)){
            $query = $query->whereDate('reimbursment_request.created_at','<=',$end_date);
        }
        $query = $query->orderby('reimbursment_request.created_at','DESC')
        ->get(); 

        $result = array();
        $index = 5;
        $total = 0;
        $no = 1;
        foreach($query as $list){

            $transaction_detail_id = $list->transaction_detail_id;
            $user_id = $list->user_id;
            $plafon_id = $list->plafon_id;
            $detail_created = $list->detail_created;

            $limit_plafon = $this->DashboardModel->limit_plafon_detail($transaction_detail_id, $user_id, $plafon_id, $detail_created);
            $usage = $limit_plafon['usage'];

            // balance
            $balance = $limit_plafon['base_plafon'] - ($list->amount+$usage);

            // status
            $id = $list->id; 
            $TransactionModel = $this->TransactionModel->status_approval_byId($id);
            $status = '';
            if(count($TransactionModel)>0){
                foreach($TransactionModel as $tm){
                    $approval_name = $tm->name;
                    $approval_status = $tm->label;

                    $status .= $approval_name." - ".$approval_status."; ";
                }
            }

            $result['data'][$index] = array(
                "A"=>$no,
                "B"=>$list->request_id,
                "C"=>$list->effective_date,
                "D"=>$list->created_at,
                "E"=>$list->emp_id,
                "F"=>$list->name,
                "G"=>$list->company_name,
                "H"=>$list->division,
                "I"=>$list->item,
                "J"=>$list->description,
                "K"=>$list->treatment,
                "L"=>$limit_plafon['base_plafon'],
                "M"=>$limit_plafon['usage'],
                "N"=>$list->amount,
                "O"=>$balance,
                "P"=>$status,
            );
            $total = $total+$list->amount;
            $index++;
            $no++;
        }
        $result['total'] = $total;
        return $result;
    }

    function download_report(Request $request){

        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');

        $data = $this->generated_data($start_date,$end_date);

        // test code start

        // $response = array(
        //     "status"=>"200",
        //     "metadata"=>array(
        //         "message"=>"Success",
        //         "data"=>$data,
        //     )
        // );

        // return response()->json($response);

        // test code end

        $template_file = base_path('public/template/template_reimbursement.xlsx');
        $filename="report_summary_reimbursment.xlsx";

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($template_file);

        /**
         *  Data start at cell A5 until K5 = 11 column 
         *  [1. No, 2. Tanggal Invoice, 3. Tanggal Pengajuan, 4. Karyawan, 5. Divisi, 6. Jenis Klaim Kesehatan, 7. Jenis Penyakit, 8. Tempat Pengobatan, 9. BANK, 10. No. Rek, 11. Jumlah Tagihan]
         *  row 6 is total data now
        */
        $row_start = 5; // constanta because data start at cell A5
        $row_inserted = count($data['data'])-1; // minus 1 because row 5 part of counting
        $row_end = $row_start+$row_inserted; // Adding row
        $spreadsheet->getActiveSheet()->insertNewRowBefore(6, $row_inserted);
        $worksheet = $spreadsheet->getSheet(0);

        //column must filled in template
        $array_column = ['A','B','C','D','E','F','G','H','K'];
        $array_column_count = count($array_column);

        for($i=5; $i<=$row_end; $i++){ //row
            for($j=0; $j<$array_column_count; $j++){ // column from in array define
                $cell = $array_column[$j].$i;
                $worksheet->getCell($cell)->setValue($data['data'][$i][$array_column[$j]]);
            }
        }

        //fill periode cell
        $periode_cell = "C3";
        $periode_start = '';
        $periode_end = '';
        if($start_date){
            $periode_start = $start_date;
        }
        if($end_date){
            $periode_end = $end_date;
        }
        $periode = "Periode: ".$periode_start.' - '.$periode_end;
        $worksheet->getCell($periode_cell)->setValue($periode); 

        //fill total cell
        $total_cell_row = $row_end+1;
        $total_cell_column = $array_column_count-1;
        $total_cell = $array_column[$total_cell_column].$total_cell_row;
        $worksheet->getCell($total_cell)->setValue($data['total']); 
        

        try{
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save(base_path('public/export/'.$filename));
            
        }catch (Exception $error){
            //handle exception
            return $error;
        }

        return response()->download(base_path('public/export/'.$filename))->deleteFileAfterSend(true);
        
    } 
}

?>