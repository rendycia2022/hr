<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return '"HR" '.$router->app->version();
});

$router->group(['prefix' => 'api/'], function () use ($router) {

    // public API for user other apps CIA
    $router->group(['prefix' => 'public'], function () use ($router) {

        $router->get('/user','public\UserController@get');
        $router->get('/user/{id}','public\UserController@getById');

    }); 

    //Id generator start
    $router->get('generator/id/class1/{total_data}','Generator@getId_class1');
    $router->get('generator/id/class4/{total_data}','Generator@getId_class4');

    //Id generator end

    $router->group(['prefix' => 'auth'], function () use ($router) {
        $router->get('/token','auth\TokenController@get');
        
        //Login Controller
        $router->get('/login','auth\Login@get');
        $router->post('/login','auth\Login@store');
        $router->put('/login','auth\Login@put');

        //Signup Controller
        $router->get('/signup','auth\Signup@get');
        $router->post('/signup','auth\Signup@store');

        //Forgot Controller
        $router->post('/forgot','auth\Forgot@store');
        $router->put('/forgot/reset','auth\Forgot@put');

    });
    $router->group(['prefix' => 'main'], function () use ($router) {
        //Dashboard Controllers
        $router->get('/dashboard','main\Dashboard@get');
    });

    //Stored files in server
    $router->group(['prefix' => 'files'], function () use ($router) {
        //Dashboard Controllers
        $router->post('/upload/{item_id}','files\UploadFiles@store');
    });

    //HR Controllers start
    
    //setting
    $router->get('/setting/general','hr\setting\General@get');
    $router->put('/setting/general','hr\setting\General@put');

    //Reimbursment Controllers
    $router->group(['prefix' => 'reimbursment'], function () use ($router) {
        //dashboard
        $router->get('/dashboard','hr\reimbursment\Dashboard@get');
        $router->get('/dashboard/approval','hr\reimbursment\Dashboard@get_approval');
        $router->get('/dashboard/paid','hr\reimbursment\Dashboard@get_paid');
        $router->get('/dashboard/plafon','hr\reimbursment\Dashboard@get_plafon');

        $router->group(['prefix' => 'data'], function () use ($router) {
            //transaction Controllers
            $router->get('/transaction/id/{id}','hr\reimbursment\data\Transaction@getById');
            $router->get('/transaction/report','hr\reimbursment\data\Transaction@getForReport');
            $router->get('/transaction/report/download','hr\reimbursment\data\Transaction@download_report');
            

            //transaction_detail Controllers
            $router->get('/transaction_detail/{id}','hr\reimbursment\data\Transaction_detail@get');
            $router->get('/transaction_detail/{user_id}/{plafon_id}','hr\reimbursment\data\Transaction_detail@getByUserAndPlafon');

            //approval Controllers
            $router->get('/getByTransaction/{transaction_id}','hr\reimbursment\data\Approval@getByTransaction');
            $router->post('/approval/reorder','hr\reimbursment\data\Approval@reorder');
            $router->delete('/approval','hr\reimbursment\data\Approval@destroy');

            //paid Controllers
            $router->get('/payment','hr\reimbursment\data\Payment@get');
            $router->put('/payment/{id}','hr\reimbursment\data\Payment@paid');
            $router->post('/payment/summary','hr\reimbursment\data\Payment@summary');
            $router->get('/payment/pdf/{id}','hr\reimbursment\data\Payment@getPDF');

            //users Controllers
            $router->get('/users','hr\reimbursment\data\Users@get');
            $router->get('/users/id','hr\reimbursment\data\Users@getById');
            $router->post('/users/password','hr\reimbursment\data\Users@store_password');
            $router->get('/users/email','hr\reimbursment\data\Users@getEmail');

            //company Controllers
            $router->get('/company','hr\reimbursment\data\Company@get');

        });

        

        //user
        $router->get('/users','hr\reimbursment\Users@get');
        $router->post('/users','hr\reimbursment\Users@store');
        $router->put('/users','hr\reimbursment\Users@put');
        $router->delete('/users','hr\reimbursment\Users@destroy');
        $router->delete('/users/burst','hr\reimbursment\Users@destroyBurst');
        $router->delete('/users/approval','hr\reimbursment\Users@destroyByAprroval');

        //employee
        $router->get('/employee/user','hr\reimbursment\Employee@getById'); 

        //approval
        $router->get('/approval/options','hr\reimbursment\Approval@options');
        $router->get('/approval/id','hr\reimbursment\Approval@getById');
        $router->get('/approval','hr\reimbursment\Approval@get');
        
        //job
        $router->get('/jobs','hr\reimbursment\Jobs@get');

        //role
        $router->get('/role','hr\reimbursment\Role@get');
        $router->get('/role/user','hr\reimbursment\Role@role_user');

        //plafon
        $router->get('/plafon','hr\reimbursment\Plafon@get');
        $router->get('/plafon/user','hr\reimbursment\Plafon@getByUser');
        $router->post('/plafon','hr\reimbursment\Plafon@store');
        $router->put('/plafon','hr\reimbursment\Plafon@put');
        $router->delete('/plafon','hr\reimbursment\Plafon@destroy');
        $router->delete('/plafon/burst','hr\reimbursment\Plafon@destroyBurst');

        //plafon_setting
        $router->get('/plafon_setting/plafon_id','hr\reimbursment\Plafon_setting@getById');
        $router->put('/plafon_setting','hr\reimbursment\Plafon_setting@put');

        //transaction
        $router->get('/transactions','hr\reimbursment\Transactions@get');
        $router->get('/transactions/getid','hr\reimbursment\Transactions@getId');
        $router->post('/transactions','hr\reimbursment\Transactions@store');
        $router->delete('/transactions','hr\reimbursment\Transactions@destroy');
        $router->delete('/transactions/clear','hr\reimbursment\Transactions@clear_data');

        //transaction_detail
        $router->get('/transaction_detail','hr\reimbursment\Transactions_detail@get');
        $router->get('/transaction_detail/getid','hr\reimbursment\Transactions_detail@getId');
        $router->get('/transaction_detail/plafon_id','hr\reimbursment\Transactions_detail@getPlafon');
        $router->post('/transaction_detail','hr\reimbursment\Transactions_detail@store');
        $router->delete('/transaction_detail','hr\reimbursment\Transactions_detail@destroy');
        $router->post('/transaction_detail/onUpload','hr\reimbursment\Transactions_detail@onUpload');
        $router->get('/transaction_detail/{request_id}/{plafon_id}','hr\reimbursment\Transactions_detail@getPlafonById'); //limit plafon in transaction_detail.vue

        //transaction_approval
        $router->get('/transactions/approval','hr\reimbursment\Transactions_approval@get');
        $router->post('/transactions/approval','hr\reimbursment\Transactions_approval@store');
        $router->post('/transactions/approval/burst','hr\reimbursment\Transactions_approval@storeBurst');
        
    });

    //HR Controllers end

    $router->group(['prefix' => 'development'], function () use ($router) {
        //Development Controllers

        // ID generating
        $router->get('/generate/id/class1/{total_data}','development\GenerateIdController@id_class1');
        $router->get('/generate/id/class4/{total_data}','development\GenerateIdController@id_class4');

        // product
        $router->get('/products','development\ProductsController@get');
        $router->post('/products','development\ProductsController@create');
        $router->delete('/products','development\ProductsController@destroy');
        
        $router->get('/products/createid','development\ProductsController@created_id');

        // product header
        $router->get('/products/header','development\ProductsHeaderController@get');
        $router->post('/products/header','development\ProductsHeaderController@create');
        $router->put('/products/header','development\ProductsHeaderController@put');
        $router->delete('/products/header','development\ProductsHeaderController@destroy');

        $router->put('/products/header/drag','development\ProductsHeaderController@drag');
        $router->get('/products/header/type','development\ProductsHeaderController@getType');
        


    });

});

?>
