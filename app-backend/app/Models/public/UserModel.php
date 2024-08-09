<?php

namespace App\Models\public;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserModel extends Model
{
    public function getById($user_id){ 

        $data = array();

        //collect data user
        $query = DB::table('users')
        ->select(
            //users
            'users.id',
            'users.email',

            //employee
            'employee.name',
            'employee.division',

            //company
            'employee.company_id as company_id',
            'company.name as company_name',
        )
        ->leftjoin('employee',"employee.user_id","=","users.id")
        ->leftjoin('role',"role.id","=","users.role")
        ->leftjoin('company',"company.id","=","employee.company_id")
        ->where('users.id',$user_id)
        ->where('users.active','1')
        ->get();
        
        if(count($query)>0){
            foreach($query as $list){
                $data = array(
                    "id"=>$list->id,
                    "email"=>$list->email,
                    "name"=>$list->name,
                    "division"=>$list->division,
                    "company_id"=>$list->company_id,
                    "company_name"=>$list->company_name,
                );
            }
        }
        
        return $data;
        
    }

}