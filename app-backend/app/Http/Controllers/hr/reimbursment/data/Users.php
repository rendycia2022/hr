<?php

namespace App\Http\Controllers\hr\reimbursment\data;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

class Users extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    function getUserById($id){
        $data = DB::table('users')
        ->select('users.email','users.id','users.password')
        ->where('id',$id)
        ->first();

        return $data;
    }

    function get_plafon($user_id){

        $result = array();
        
        // get job level from employee
        $query = DB::table('employee')
        ->select('job')
        ->where('user_id', $user_id)
        ->first();

        // get plafon from job in employee
        $query2 = DB::table('plafon')
        ->where('job_level', $query->job)
        ->get();

        // build data plafon
        foreach($query2 as $list2){
            $item = $list2->item;
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

            //get plafon
            $plafon = $base_plafon - $total_amount;
            
            $result[] = array(
                "plafon_id"=>$plafon_id,
                "item"=>$item,
                "base_plafon"=>$base_plafon,
                "usage"=>$total_amount,
                "plafon"=>$plafon,
            );

        }

        return $result;
        
    }

    function get(Request $request){

        $queryUsers = DB::table('employee')
        ->select('users.id','employee.name', 'employee.emp_id', 'employee.date_join', 'jobs.level', 'jobs.grade')
        ->leftjoin("users","users.id","=","employee.user_id")
        ->leftjoin("jobs","jobs.id","=","employee.job")
        ->where('users.active',1)
        ->orderby('employee.name', 'ASC')
        ->get();

        $users = array();
        foreach($queryUsers as $listUsers){
            $user_id = $listUsers->id;

            $plafon[$user_id] = $this->get_plafon($user_id);
            $count_plafon_list = count($plafon[$user_id]);

            for($j=0; $j<$count_plafon_list; $j++){
                $users[] = array(
                    "id"=>$listUsers->id,
                    "name"=>$listUsers->name,
                    "emp_id"=>$listUsers->emp_id,
                    "date_join"=>$listUsers->date_join,
                    "level"=>$listUsers->level,
                    "grade"=>$listUsers->grade,
                    "plafon_id"=>$plafon[$user_id][$j]['plafon_id'],
                    "item"=>$plafon[$user_id][$j]['item'],
                    "base_plafon"=>$plafon[$user_id][$j]['base_plafon'],
                    "usage"=>$plafon[$user_id][$j]['usage'],
                    "plafon"=>$plafon[$user_id][$j]['plafon'],
                );
            }

            /* 
            
            */

        }
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "users"=>$users,
            )
        );

        return response()->json($response);
        
    }

    function getById(Request $request){

        $payload_id = $request->input('id');

        $info = DB::table('employee')
        ->select('employee.name', 'employee.emp_id', 'employee.date_join', 'jobs.level', 'jobs.grade')
        ->leftjoin("jobs","jobs.id","=","employee.job")
        ->where('user_id',$payload_id)
        ->first();

        $account = $this->getUserById($payload_id);
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "info"=>$info,
                "account"=>$account,
            )
        );

        return response()->json($response);
        
    }

    function store_password(Request $request){

        $payload = $request->json()->all();

        $query = $this->getUserById($payload['user_id']);

        if($query){
            if(Hash::check($payload['current'], $query->password)){
                DB::table('users')
                ->where('id', $payload['user_id'])
                ->update(
                    [
                        'password' => Hash::make($payload['new']),
                        'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                    ]
                );
                $status = '200';
                $message = "Attention";

            }else{
                $status = '401';
                $message = "Unauthorized";
            }
            
        }else{
            $status = '404';
            $message = "Not found";
        }

        $response = array(
            "status"=>$status,
            "metadata"=>array(
                "message"=>$message,
            )
        );

        return response()->json($response);
        
    }

    function getEmail(Request $request){

        $payload_query = $request->input('query');

        $query = DB::table('users')
        ->select('email')
        ->where('email', 'like',$payload_query . '%')
        ->whereNotIn('email', ['administrator@mail.com'])
        ->where('active',1)
        ->get();

        $message = array();

        foreach($query as $list){
            $message[] = $list->email;
        }
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>$message,
            )
        );

        return response()->json($response);
        
    }
}

?>