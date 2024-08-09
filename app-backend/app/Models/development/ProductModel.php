<?php

namespace App\Models\development;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductModel extends Model
{

    public function __construct(){
        $this->ProductHeaderModel = new ProductHeaderModel;
    }

    public function getByUser($user_id){

        $data = array();

        //collect data from product
        $query = DB::table('z_dev_product')
            ->select(
                'z_dev_product.id',
                'z_dev_product.created_at',
                'z_dev_product.updated_at',
                'z_dev_product.user_id',
                'z_dev_product.active',
            )
            ->where('z_dev_product.user_id',$user_id)
            ->where('z_dev_product.active','1')
            ->get();
        
        if(count($query)>0){
            $index = 0;
            foreach($query as $list){
                //define data
                $product_id = $list->id;
                $product_created_at = $list->created_at;
                $product_updated_at = $list->updated_at;
                $product_active = $list->active;
    
                //collect data from product_detail
                $query2 = DB::table('z_dev_product_detail')
                ->select(
                    'z_dev_product_detail.id',
                    'z_dev_product_detail.product_id',
                    'z_dev_product_detail.header_id',
                    'z_dev_product_detail.value',
                )
                ->where('z_dev_product_detail.product_id',$product_id)
                ->get();

                if(count($query2)>0){
                    // assign data product
                    $data[$index]['id'] = $product_id;
                    $data[$index]['created_at'] = $product_created_at;
                    $data[$index]['updated_at'] = $product_updated_at;
                    $data[$index]['active'] = $product_active;
                    
                    // assign data product detail 
                    foreach($query2 as $list2){
                        $productDetail_header_id = $list2->header_id;
                        $productDetail_value = $list2->value;
                        $data[$index][$productDetail_header_id] = $productDetail_value;
                    }
                }

                $index++;
            }
        }
        
        
        return $data;
        
    }

    public function getById($id){

        $data = array();

        //collect data from product
        $query = DB::table('z_dev_product')
            ->select(
                'z_dev_product.id',
                'z_dev_product.created_at',
                'z_dev_product.updated_at',
                'z_dev_product.user_id',
                'z_dev_product.active',
            )
            ->where('z_dev_product.id',$id)
            ->where('z_dev_product.active','1')
            ->get();
        
        if(count($query)>0){
            foreach($query as $list){
                //define data
                $product_id = $list->id;
                $product_created_at = $list->created_at;
                $product_updated_at = $list->updated_at;
                $product_active = $list->active;
    
                //collect data from product_detail
                $query2 = DB::table('z_dev_product_detail')
                ->select(
                    'z_dev_product_detail.id',
                    'z_dev_product_detail.product_id',
                    'z_dev_product_detail.header_id',
                    'z_dev_product_detail.value',
                )
                ->where('z_dev_product_detail.product_id',$product_id)
                ->get();

                if(count($query2)>0){
                    // assign data product
                    $data['id'] = $product_id;
                    $data['created_at'] = $product_created_at;
                    $data['updated_at'] = $product_updated_at;
                    $data['active'] = $product_active;
                    
                    // assign data product detail 
                    foreach($query2 as $list2){
                        $productDetail_header_id = $list2->header_id;
                        $productDetail_value = $list2->value;
                        $data[$productDetail_header_id] = $productDetail_value;
                    }
                }
            }
        }
        
        
        return $data;
        
    }
    
    public function getByHeaderId($user_id, $header_id){

        $query = DB::table('z_dev_product')
        ->select(
            'z_dev_product.id',
            'z_dev_product.created_at',
            'z_dev_product.updated_at',
            'z_dev_product.user_id',
            'z_dev_product.active',
        )
        ->where('z_dev_product.user_id',$user_id)
        ->where('z_dev_product.active','1')
        ->get();
        
        $data = null;
        if(count($query) > 0){
            foreach($query as $list){

                $product_id = $list->id;

                // getting detail
                $query2 = DB::table('z_dev_product_detail')
                ->select(
                    'z_dev_product_detail.id',
                    'z_dev_product_detail.product_id',
                    'z_dev_product_detail.header_id',
                    'z_dev_product_detail.value',
                )
                ->where('z_dev_product_detail.product_id',$product_id)
                ->where('z_dev_product_detail.header_id',$header_id)
                ->get();
                    
                if(count($query2)>0){
                    foreach($query2 as $list2){
                        $data = array(
                            "header_id"=>$list2->header_id,
                            "value"=>$list2->value,
                        );
                    }
                }

            }
        }
        
        return $data;
        
    }

}