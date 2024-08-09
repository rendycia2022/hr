<?php

namespace App\Http\Controllers\hr\reimbursment;
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

    function getById($user_id){

        $query = DB::table('users')
        ->select('users.*','role.name as role_name','employee.*','jobs.level','jobs.grade','company.id as company_id','company.name as company_name')
        ->where('users.id',$user_id)
        ->leftjoin("employee","employee.user_id","=","users.id")
        ->leftjoin("company","company.id","=","employee.company_id")
        ->leftjoin("jobs","jobs.id","=","employee.job")
        ->leftjoin("role","role.id","=","users.role")
        ->get();
        $result = array();

        $approval = array();
        foreach($query as $list){

            $query2 = DB::table('approval')
            ->leftjoin("users","users.id","=","approval.approval")
            ->where('user_id',$list->id)
            ->where('approval.active',1)
            ->get();

            foreach($query2 as $list2){
                $approval[] = array(
                    "sort"=>$list2->sort,
                    "id"=>$list2->approval,
                    "email"=>$list2->email,
                );
            };

            $result = array(
                "id"=>$list->id,
                "email"=>$list->email,
                "role"=>array(
                    "id"=>$list->role,
                    "name"=>$list->role_name,
                ),
                "name"=>$list->name,
                "division"=>$list->division,
                "date_join"=>$list->date_join,
                "emp_id"=>$list->emp_id,
                "company"=>array(
                    "id"=>$list->company_id,
                    "name"=>$list->company_name,
                ),
                "job"=>array(
                    "code"=>$list->job,
                    "level"=>$list->level,
                    "grade"=>$list->grade,
                ),
                "approval_list"=>$approval,
            );

            $approval = array();
        }

        return $result;
    }

    function get(Request $request){

        $payload_id = $request->input('id');

        $role_user = DB::table('users')
        ->where('id',$payload_id)
        ->first();

        $query = DB::table('users')
        ->select('users.*','role.name as role_name','employee.*','jobs.level','jobs.grade','company.id as company_id','company.name as company_name')
        ->where('users.role','>=',$role_user->role)
        ->where('users.active',1)
        ->leftjoin("employee","employee.user_id","=","users.id")
        ->leftjoin("company","company.id","=","employee.company_id")
        ->leftjoin("jobs","jobs.id","=","employee.job")
        ->leftjoin("role","role.id","=","users.role")
        ->get();
        $result = array();

        $approval = array();
        foreach($query as $list){

            $query2 = DB::table('approval')
            ->leftjoin("users","users.id","=","approval.approval")
            ->where('user_id',$list->id)
            ->where('approval.active',1)
            ->orderBy('approval.sort', 'ASC')
            ->get();

            foreach($query2 as $list2){
                $approval[] = array(
                    "id"=>$list2->approval,
                    "email"=>$list2->email,
                    "sort"=>$list2->sort,
                );
            };

            $result[] = array(
                "id"=>$list->id,
                "email"=>$list->email,
                "role"=>array(
                    "id"=>$list->role,
                    "code"=>$list->role,
                    "name"=>$list->role_name,
                ),
                "name"=>$list->name,
                "division"=>$list->division,
                "date_join"=>$list->date_join,
                "emp_id"=>$list->emp_id,
                "company"=>array(
                    "id"=>$list->company_id,
                    "name"=>$list->company_name,
                ),
                "job"=>array(
                    "code"=>$list->job,
                    "level"=>$list->level,
                    "grade"=>$list->grade,
                ),
                "approval_list"=>$approval,
            );

            $approval = array();
        }
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "data"=>$result,
            )
        );

        return response()->json($response);
        
    }

    function store(Request $request){

        $payload = $request->json()->all();

        $id = Uuid::uuid4();

        // insert users
        DB::table('users')->insert(
            [
                'id'=>$id,
                'email'=>$payload['email'],
                'password'=>Hash::make('123456'),
                'role' => $payload['role']['id'],
                'created_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
            ]
        );

        // insert employee
        DB::table('employee')->insert(
            [
                'name'=>$payload['name'],
                'division'=>$payload['division'],
                'date_join'=>$payload['date_join'],
                'emp_id'=>$payload['emp_id'],
                'user_id'=>$id,
                'job' => $payload['job']['code'],
                'created_by'=>$payload['user_local'],
                'updated_by'=>$payload['user_local'],
                'created_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                'company_id'=>$payload['company']['id'],
            ]
        );

        // insert approval
        if(isset($payload['approval'])){
            $count_approval = count($payload['approval']);

            for($i=0; $i<$count_approval; $i++){
                $query = DB::table('users')
                ->where('email',$payload['approval'][$i])
                ->first();
                if($query){
                    $query2 = DB::table('approval')
                    ->where('user_id',$payload['id'])
                    ->where('approval',$query->id)
                    ->first();
                    if(empty($query2)){
                        $sort = $this->sortApproval($payload['id']); //sorting approval
                        DB::table('approval')->insert(
                            [
                                'user_id'=>$payload['id'],
                                'approval'=>$query->id,
                                'created_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                                'sort'=>$sort,
                            ]
                        );
                    }
                }    
            }
        }

        $data = $this->getById($id);
        
        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "data"=>$data,
            )
        );

        return response()->json($response);
        
    }

    function put(Request $request){ 

        $payload = $request->json()->all();

        DB::table('users')
        ->where('id', $payload['id'])->update(
            [
                'email'=>$payload['email'],
                'role' => $payload['role']['id'],
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
            ]
        );

        DB::table('employee')
        ->where('user_id', $payload['id'])->update(
            [
                'name'=>$payload['name'],
                'division'=>$payload['division'],
                'date_join'=>$payload['date_join'],
                'emp_id'=>$payload['emp_id'],
                'job' => $payload['job']['code'],
                'updated_by'=>$payload['user_local'],
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                'company_id'=>$payload['company']['id'],
            ]
        );

        if(isset($payload['approval'])){

            // insert approval
            $count_approval = count($payload['approval']);

            for($i=0; $i<$count_approval; $i++){
                $query = DB::table('users')
                ->where('email',$payload['approval'][$i])
                ->where('active', 1)
                ->first();
                if($query){
                    $query2 = DB::table('approval')
                    ->where('user_id',$payload['id'])
                    ->where('approval',$query->id)
                    ->where('active', 1)
                    ->first();
                    if(empty($query2)){
                        $sort = $this->sortApproval($payload['id']); //sorting approval
                        DB::table('approval')->insert(
                            [
                                'user_id'=>$payload['id'],
                                'approval'=>$query->id,
                                'created_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                                'sort'=>$sort,
                            ]
                        );
                    }    
                }    
            }
        }

        $data = $this->getById($payload['id']);

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "data"=>$data,
            )
        );

        return response()->json($response);
        
    }

    function sortApproval($user_id){
        $query = DB::table('approval')
        ->where('user_id', $user_id)
        ->where('active', 1)
        ->get();

        if(!isset($query)){
            $result = 1;
        }else{
            $count = count($query);
            $result = $count+1;
        }
        
        return $result;

    }

    function destroy(Request $request){

        $payload = $request->json()->all(); 

        DB::table('users')->where('id', $payload['data']['id'])->update(
            [
                'active' =>0,
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
            ]
        );
        DB::table('approval')->where('user_id', $payload['data']['id'])->update(
            [
                'active' =>0,
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

    function destroyBurst(Request $request){

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

    function destroyByAprroval(Request $request){

        $payload = $request->json()->all();

        DB::table('approval')
        ->where('user_id', $payload['data']['user_id'])
        ->where('approval', $payload['data']['approval'])
        ->update(
            [
                'active' =>0,
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