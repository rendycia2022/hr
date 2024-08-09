<?php

namespace App\Http\Controllers\development;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

//models
use App\Models\development\ProductHeaderModel;

class ProductsHeaderController extends BaseController
{
    public function __construct(){
        $this->ProductHeaderModel = new ProductHeaderModel;
    }

    function get(Request $request){

        //define data params
        $params_user_id = $request->input('user_id');

        $query = $this->ProductHeaderModel->getByUser($params_user_id);

        if(count($query)>0){
            $status = 200;
            $message = 'Fetching data completed';
            $result = $query;
        }else{
            $status = 404;
            $message = 'Data not found';
            $result = array();
        }

        $response = array(
            "status"=>$status,
            "metadata"=>array(
                "message"=>$message,
                "result"=>$result,
            )
        );
        
        return response()->json($response);
        
    }

    function create(Request $request){

        //define data payload
        $payload = $request->json()->all();
        $user_id = $payload['userId'];

        if($user_id){
            // define data
            $uniqueId = Uuid::uuid4();
            $type = 'text';

            // get index 
            $query = DB::table('z_dev_product_header')
            ->select('index')
            ->where('user_id', $user_id)
            ->where('active', 1)
            ->orderby('index','DESC')
            ->first();

            if(isset($query)){
                $queryIndex = $query->index;
                if($queryIndex === ''){
                    $response = array(
                        "status"=>500,
                        "message"=>'Internal server error',
                    );
                    
                    return response()->json($response);
                }else{
                    $index = $queryIndex+1;
                }
                
            }else{
                $index = 1;
            }

            // insert users
            DB::table('z_dev_product_header')->insert(
                [
                    'id'=>$uniqueId,
                    'created_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                    'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                    'user_id'=>$user_id,
                    'index'=>$index,
                    'type'=>$type,
                ]
            );
            
        }
        
        // Resync data
        $result = $this->ProductHeaderModel->getByUser($user_id);

        if(count($result)>0){
            $status = 200;
            $message = 'Fetching data completed';
        }else{
            $status = 404;
            $message = 'Data not found';
            $result = array();
        }
        

        $response = array(
            "status"=>$status,
            "metadata"=>array(
                "message"=>$message,
                "result"=>$result,
            )
        );
        
        return response()->json($response);
        
    }

    function put(Request $request){

        //define data
        $payload = $request->json()->all();
        $payload_id = $payload['id'];
        $headerModel = new ProductHeaderModel;

        // updating data
        DB::table('z_dev_product_header')
            ->where('id', $payload_id)
            ->update(
                [
                    'title'=>$payload['title'],
                    'placeholder'=>$payload['placeholder'],
                    'type'=>$payload['type_options']['value'],
                    'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                ]
            );

        $result = $this->ProductHeaderModel->getById($payload_id);

        if(count($result)>0){
            $status = 200;
            $message = 'Fetching data completed';
        }else{
            $status = 404;
            $message = 'Data not found';
            $result = array();
        }
        

        $response = array(
            "status"=>$status,
            "metadata"=>array(
                "message"=>$message,
                "result"=>$result,
            )
        );
        
        return response()->json($response);
        
    }

    function destroy(Request $request){

        //define data payload
        $payload = $request->json()->all();
        $user_id = $request->input('user_id');

        $count = count($payload['data']);
        if($count > 0){
            // collect selected id
            for($i=0; $i<$count; $i++){
                $id[] = $payload['data'][$i]['id'];
            };
            
            // deleting data by active = 0
            DB::table('z_dev_product_header')->whereIn('id', $id)->update(
                [
                    'active' =>0,
                    'index' =>null,
                    'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                ]
            );
        }

        $status = 200;
        $message = array(
            "severity"=>"error", 
            "summary"=>"Attention", 
            "detail"=>"Data has been deleted",
        );
        
        $response = array(
            "status"=>$status,
            "metadata"=>array(
                "message"=>$message,
            )
        );
        
        return response()->json($response);
        
    }

    function drag(Request $request){ 

        $payload = $request->json()->all();

        // define data payload
        $user_id = $payload['user_id'];
        $drag_index = $payload['drag_index'];
        $drop_index = $payload['drop_index'];

        // collect data header drag
        $query_index = DB::table('z_dev_product_header')
            ->select('id','title','index')
            ->where('user_id',$user_id)
            ->where('index',$drag_index)
            ->first();

        if($drag_index < $drop_index){
            // index--

            // getting data from $drop_index to $drag_index 
            $query1 = DB::table('z_dev_product_header')
            ->select('id','index')
            ->where('user_id',$user_id)
            ->where('index','<=',$drop_index)
            ->where('index','>',$query_index->index)
            ->orderBy('index','DESC')
            ->get();

            foreach($query1 as $list1){
                // updating headers index with $list1->index - 1
                DB::table('z_dev_product_header')
                ->where('user_id', $user_id)
                ->where('id', $list1->id)
                ->update(
                    [
                        'index'=>$list1->index-1,
                        'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                    ]
                );
            }

        }else{
            // index++
            
            // getting data from $drop_index to $drag_index 
            $query1 = DB::table('z_dev_product_header')
            ->select('id','index')
            ->where('user_id',$user_id)
            ->where('index','<',$query_index->index)
            ->where('index','>=',$drop_index)
            ->orderBy('index','DESC')
            ->get();

            foreach($query1 as $list1){
                // updating headers index with $list1->index + 1
                DB::table('z_dev_product_header')
                ->where('user_id', $user_id)
                ->where('id', $list1->id)
                ->update(
                    [
                        'index'=>$list1->index+1,
                        'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
                    ]
                );
            }
        }

        DB::table('z_dev_product_header')
        ->where('user_id', $user_id)
        ->where('id', $query_index->id)
        ->update(
            [
                'index'=>$drop_index,
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
            ]
        );

        $status = 200;
        $message = 'Sync data success';

        $response = array(
            "status"=>$status,
            "metadata"=>array(
                "message"=>$message,
            )
        );
        
        return response()->json($response);
        
    }

    function getType(Request $request){

        $query = DB::table('z_dev_header_type')
        ->select('label','value')
        ->where('active',1)
        ->orderby('index', 'ASC')
        ->get();

        if(count($query)>0){
            $status = 200;
            $message = 'Fetching data completed';
            $result = $query;
        }else{
            $status = 404;
            $message = 'Data not found';
            $result = array();
        }

        $response = array(
            "status"=>$status,
            "metadata"=>array(
                "message"=>$message,
                "type"=>$result,
            )
        );
        
        return response()->json($response);
        
    }

}

?>