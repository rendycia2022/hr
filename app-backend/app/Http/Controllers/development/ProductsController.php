<?php

namespace App\Http\Controllers\development;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

//models
use App\Models\development\ProductHeaderModel;
use App\Models\development\ProductModel;

class ProductsController extends BaseController
{
    public function __construct(){
        $this->ProductHeaderModel = new ProductHeaderModel;
        $this->ProductModel = new ProductModel;
    }
    
    function created_id(Request $request){

        //define data
        $user_id = $request->input('user_id');
        $type = 'auto';

        $query = DB::table('z_dev_product_header')
        ->select(
            'z_dev_product_header.id',
            'z_dev_product_header.title',
            'z_dev_product_header.type'
        )
        ->where('z_dev_product_header.user_id',$user_id)
        ->where('z_dev_product_header.active','1')
        ->where('z_dev_product_header.type',$type)
        ->get();
        
        $data = array();
        $message = 'Data not found';

        if(count($query)>0){

            foreach($query as $list){
                $data[] = array(
                    "id"=>$list->id,
                    "uniqueId"=>Uuid::uuid4(),
                );
            }
            
            $message = 'Getting data completed';

        }
        
        $status = 200;

        $response = array(
            "status"=>$status,
            "metadata"=>array(
                "message"=>$message,
                "unique"=>$data,
            )
        );
        
        return response()->json($response);
        
    }

    function get(Request $request){

        //define data params
        $user_id = $request->input('user_id');

        $products = $this->ProductModel->getByUser($user_id);
        
        $status = 200;
        $message = 'Fetching data completed';

        $response = array(
            "status"=>$status,
            "metadata"=>array(
                "message"=>$message,
                "products"=>$products,
            )
        );
        
        return response()->json($response);
        
    }

    function create(Request $request){

        //define data payload
        $payload = $request->json()->all();
        $user_id = $payload['user_id'];

        // collecting data
        $data = array();
        
        //product data
        $product_id = Uuid::uuid4();
        $product_timestamp = gmdate('Y-m-d H:i:s', time()+(60*60*7));

        //collection data main
        $data['product'] = array(
            "id"=>$product_id,
            "created_at"=>$product_timestamp,
            "updated_at"=>$product_timestamp,
            "user_id"=>$user_id,
        );

        // getting index from header
        $headers = $this->ProductHeaderModel->getByUser($user_id);
        $array_header_id = array();
        for($h=0; $h<count($headers); $h++){
            // define data
            $detail_header_id = $headers[$h]['id'];
            $detail_header_title = $headers[$h]['title'];
            if(empty($payload[$headers[$h]['id']])){
                // empty data
                $message = array(
                    "severity"=>"error", 
                    "summary"=>"Failed", 
                    "detail"=>$detail_header_title." is Empty",
                );

                $response = array(
                    "status"=>500,
                    "metadata"=>array(
                        "message"=>$message,
                    )
                );
                
                return response()->json($response); 
            }else{
                $detail_value = $payload[$headers[$h]['id']];
            }
            

            //collection data detail
            $data['product_detail'][] = array(
                "id"=>Uuid::uuid4(),
                "product_id"=>$product_id,
                "header_id"=>$detail_header_id,
                "value"=>$detail_value,
            );

            // define data for rules
            $detail_header_type = $headers[$h]['type'];

            if($detail_header_type == 'unique'){
                $array_header_id[] = array(
                    "header_id"=>$detail_header_id,
                    "header_title"=>$detail_header_title,
                    "value"=>$payload[$detail_header_id],
                );
            }
        }

        $existing_data = array();
        if(count($array_header_id)>0){
            for($i=0; $i<count($array_header_id); $i++){
                //getting header 
                $existing_header = $this->ProductModel->getByHeaderId($user_id, $array_header_id[$i]['header_id']);
                if($existing_header){
                    if($array_header_id[$i]['value'] == $existing_header['value']){
                        $existing_data = $array_header_id[$i];
                    }
                }
            }
        }
        
        $product = array();
        if(empty($existing_data)){
            // data is empty
            // insert data to product
            DB::table('z_dev_product')->insert(
                $data['product']
            );

            // insert data to product_detail
            for($d=0; $d<count($data['product_detail']); $d++){
                DB::table('z_dev_product_detail')->insert(
                    $data['product_detail'][$d]
                );
            }
            
            $status = 200;
            $message = array(
                "severity"=>"success", 
                "summary"=>"Successful", 
                "detail"=>"Data Created",
            );

            
            
        }else{
            // unique data Exist
            $status = 404;
            $message = array(
                "severity"=>"warn", 
                "summary"=>"Attention", 
                "detail"=>"Data Exist",
            );

        }

        $product = $this->ProductModel->getById($product_id);

        $response = array(
            "status"=>$status,
            "metadata"=>array(
                "message"=>$message,
                "product"=>$product,
                "data_exist"=>$existing_data,
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
            DB::table('z_dev_product')->whereIn('id', $id)->update(
                [
                    'active' =>0,
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

}

?>