<?php

namespace App\Models\reimbursement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TransactionModel extends Model
{

    public function __construct(){
    }

    public function status_approval_byId($transaction_id){

        $query = DB::table('reimbursment_approval')
        ->select('reimbursment_approval.*','reimbursment_status.label', 'reimbursment_status.severity', 'employee.name')
        ->join('reimbursment_status',"reimbursment_status.id","=","reimbursment_approval.status_id")
        ->join('employee',"employee.user_id","=","reimbursment_approval.approval_id")
        ->where('reimbursment_approval.request_id', $transaction_id)
        ->orderby('reimbursment_approval.sort', 'ASC')
        ->get();

        return $query;
        
    }

}