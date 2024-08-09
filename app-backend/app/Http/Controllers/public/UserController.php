<?php

namespace App\Http\Controllers\public;
use Laravel\Lumen\Routing\Controller as BaseController;

// method
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

//models
use App\Models\public\UserModel;

class UserController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');

        //model
        $this->UserModel = new UserModel;
    }

    function get(Request $request){

        //define data from payload
        $user_id = $request->input('user_id');

        $data = $this->UserModel->getById($user_id);
        
        $response = array(
            "status"=>"200",
            "message"=>"Success",
            "metadata"=>$data,
        );

        return response()->json($response);
        
    }

    function getById(Request $request, $id){

        $data = $this->UserModel->getById($id);
        
        $response = array(
            "message"=>$data,
        );

        return response()->json($response);
        
    }
}

?>