<?php

namespace App\Models\development;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductHeaderModel extends Model
{

    public function getById($id){
        $query = DB::table('z_dev_product_header')
        ->leftjoin('z_dev_header_type','z_dev_header_type.value','=','z_dev_product_header.type')
        ->select(
            // z_dev_product_header
            'z_dev_product_header.id',
            'z_dev_product_header.title',
            'z_dev_product_header.created_at',
            'z_dev_product_header.updated_at',
            'z_dev_product_header.user_id',
            'z_dev_product_header.active',
            'z_dev_product_header.index',
            'z_dev_product_header.placeholder',
            'z_dev_product_header.type',
            
            // z_dev_header_type
            'z_dev_header_type.label',
            'z_dev_header_type.value',
        )
        ->where('id',$id)
        ->get();

        $data = array();
        if(count($query) > 0){
            foreach($query as $list){
                $data = array(
                    "id"=>$list->id,
                    "title"=>$list->title,
                    "created_at"=>$list->created_at,
                    "updated_at"=>$list->updated_at,
                    "user_id"=>$list->user_id,
                    "active"=>$list->active,
                    "index"=>$list->index,
                    "placeholder"=>$list->placeholder,
                    "type"=>$list->type,
                    "type_name"=>$list->label,
                    "type_options"=>array(
                        "label"=>$list->label,
                        "value"=>$list->value,
                    ),
                );
            }
        }
        
        return $data;
    }

    public function getByUser($user_id){

        $query = DB::table('z_dev_product_header')
        ->leftjoin('z_dev_header_type','z_dev_header_type.value','=','z_dev_product_header.type')
        ->select(
            // z_dev_product_header
            'z_dev_product_header.id',
            'z_dev_product_header.title',
            'z_dev_product_header.created_at',
            'z_dev_product_header.updated_at',
            'z_dev_product_header.user_id',
            'z_dev_product_header.active',
            'z_dev_product_header.index',
            'z_dev_product_header.placeholder',
            'z_dev_product_header.type',
            
            // z_dev_header_type
            'z_dev_header_type.label',
            'z_dev_header_type.value',
        )
        ->where('z_dev_product_header.user_id',$user_id)
        ->where('z_dev_product_header.active','1')
        ->orderBy('z_dev_product_header.index','ASC')
        ->get();

        $data = array();
        if(count($query) > 0){
            foreach($query as $list){
                $data[] = array(
                    "id"=>$list->id,
                    "title"=>$list->title,
                    "created_at"=>$list->created_at,
                    "updated_at"=>$list->updated_at,
                    "user_id"=>$list->user_id,
                    "active"=>$list->active,
                    "index"=>$list->index,
                    "placeholder"=>$list->placeholder,
                    "type"=>$list->type,
                    "type_name"=>$list->label,
                    "type_options"=>array(
                        "label"=>$list->label,
                        "value"=>$list->value,
                    ),
                );
            }
        }
        
        return $data;
        
    }

    

}