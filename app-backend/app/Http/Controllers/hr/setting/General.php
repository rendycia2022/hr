<?php

namespace App\Http\Controllers\hr\setting;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class General extends BaseController
{
    function get(Request $request){

        $category = $request->input('category');

        $query = DB::table('setting')
        ->select('setting.*')
        ->where('category',$category)
        ->get();

        $data = array();

        foreach($query as $list){
            $data[$list->name] = array(
                "id"=>$list->id,
                "name"=>$list->name,
                "value"=>$list->value,
            );
        }

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "data"=>$data,
            )
        );

        return response()->json($response);
        
    }

    function getData($id,$category){

        $query = DB::table('setting')
        ->select('setting.*')
        ->where('category',$category)
        ->get();

        $data = array();

        foreach($query as $list){
            $data[$list->name] = array(
                "id"=>$list->id,
                "name"=>$list->name,
                "value"=>$list->value,
            );
        }

        return $data;
        
    }

    function put(Request $request){ 

        $payload = $request->json()->all();

        DB::table('setting')
        ->where('id', $payload['id'])->update(
            [
                'value'=>$payload['value'],
                'updated_by'=>$payload['user_id'],
                'updated_at'=>gmdate('Y-m-d H:i:s', time()+(60*60*7)),
            ]
        );

        $data = $this->getData($payload['id'], $payload['category']);

        $response = array(
            "status"=>"200",
            "metadata"=>array(
                "message"=>"success",
                "data"=>$data,
            )
        );

        return response()->json($response);
        
    }
}

?>