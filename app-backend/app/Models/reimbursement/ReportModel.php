<?php

namespace App\Models\reimbursement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Models\reimbursement\DashboardModel;
use App\Models\reimbursement\TransactionModel;

class ReportModel extends Model
{

    public function __construct(){
        $this->DashboardModel = new DashboardModel;
        $this->TransactionModel = new TransactionModel;
    }

    public function getById($id){

        $result = array();

        $query = DB::table('reimbursment_request_detail')
        ->select(
            'reimbursment_request_detail.effective_date',
            'reimbursment_request.id',
            'reimbursment_request.request_id',
            'reimbursment_request.created_at',
            'reimbursment_request.bank_account',
            'reimbursment_request.bank_rekening',
            'reimbursment_request.bank_name',
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
            'company.id as company_id',
            'company.name as company_name',
        )
        ->join("reimbursment_request","reimbursment_request.id","=","reimbursment_request_detail.request_id")
        ->join("plafon","plafon.id","=","reimbursment_request_detail.plafon_id")
        ->join("employee","employee.user_id","=","reimbursment_request_detail.user_id")
        ->join("company","employee.company_id","=","company.id")
        ->where('reimbursment_request.id', $id)
        ->orderBy('reimbursment_request_detail.created_at', 'DESC')
        ->get();

        if(count($query)>0){
            $total_amount = 0;
            $total_balance = 0;
            $full_description = '';
            foreach($query as $list){

                $transaction_detail_id = $list->transaction_detail_id;
                $user_id = $list->user_id;
                $plafon_id = $list->plafon_id;
                $detail_created = $list->detail_created;
    
                $limit_plafon = $this->DashboardModel->limit_plafon_detail($transaction_detail_id, $user_id, $plafon_id, $detail_created);
                $base_plafon = $limit_plafon['base_plafon'];
                $usage = $limit_plafon['usage'];
    
                // total amount
                $total_amount = $total_amount + $list->amount;

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
    
                

                // data needed start

                $result['report'] = array(
                    "request_date"=>$list->created_at,
                    "emp_name"=>$list->name,
                    "emp_id"=>$list->emp_id,
                    "division"=>$list->division,
                    "company_id"=>$list->company_id,
                    "company_name"=>$list->company_name,
                    "item"=>$list->item,
                    "bank_account"=>$list->bank_account,
                    "bank_rekening"=>$list->bank_rekening,
                    "bank_name"=>$list->bank_name,

                    "base_plafon"=>$base_plafon,
                    "usage"=>$usage,
                    "total_amount"=>$total_amount,
                    "status"=>$status,
                    
                );

                // data needed end 

                // build decription
                $effective_date = $list->effective_date;
                $description = $list->description;
                $treatment = $list->treatment;
                $amount = $list->amount;
                
                
                $full_description .= '- ('.$effective_date.') '.$description.' - '.$treatment.' Rp. '.number_format($amount,2,",",".").'<br/>';
                
                $result['data'][] = array(
                    "effective_date"=>$effective_date,
                    "description"=>$description,
                    "treatment"=>$treatment,
                    "amount"=>$amount,
                );
            }

            // get description
            $result['report']['full_description'] = $full_description;

            // get balance
            $balance = $base_plafon - ($usage + $total_amount);
            $result['report']['balance'] = $balance;


        }

        return $result;
        
    }

    public function getCompany(){
        $query = DB::table('company')
        ->select(
            'company.id as company_id',
            'company.name as company_name',
            'company.code as company_code',
        )
        ->get();

        return $query;
    }

}