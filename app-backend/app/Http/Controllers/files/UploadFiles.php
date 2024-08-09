<?php

namespace App\Http\Controllers\files;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\File;

class UploadFiles extends BaseController
{
    
    function store(Request $request, $item_id){

        // create base directory
        $folder_root = 'upload/batch/';
        $path = base_path('public/'.$folder_root.$item_id.'/');
        if (!file_exists($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }

        foreach ($request->file('files') as $key => $value) {
            $file_name= $value->getClientOriginalName();
            if(file_exists($path)){
                $value->move($path, $file_name);
            }    
        }

        $response = array(
            "status"=>200,
            "metadata"=>array(
                "message"=>'Success',
                "item_id"=>$item_id,
                
            )
        );

        return response()->json($response);
        
    }
}

?>