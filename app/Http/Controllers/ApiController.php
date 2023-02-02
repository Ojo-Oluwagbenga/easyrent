<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Product as ModelProduct;
use App\Models\Bins as ModelBin;
use App\Models\User as ModelUser;

class ApiController extends Controller
{
    public function manager(Request $request, $class_name, $func_name){
        $managedclasses = [
            'User' => (new User),
            'Bin' => (new Bin),
            'Product' => (new Product),
        ];
       
        try{           
            
            $tokenfromclient = $request->header('X-CSRF-TOKEN', 'default');
            $tokenfromserver = csrf_token();
            
            if ($tokenfromclient === $tokenfromserver){                                
                $response = ($managedclasses[ucfirst($class_name)])->$func_name($request);
                return $response;
            }else{
                $ret = [
                    'response' => 'failed',
                    'reason' => 'Invalid Token',
                    'data' => 'No err',
                ];
                return json_encode($ret);
            }


        } catch (\Throwable $th) {
            $ret = [
                'response' => 'failed',
                'reason' => $th->getMessage(),
                'data' => '',
            ];
            return json_encode($ret);
        }

    }

    public function test(Request $request){
        
        $user = new ModelUser;
        $user->name = "test1";
        $user->email = "test2@test.com";
        $user->password = "Hary";
        $user->gender = "fEAER";
        $user->role = "user";
        $user->likedproducts = "testlikes";

        
        try{
            $user->save();
        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'response' => 'failed',
                'reason' => $ex->getMessage(),
                'data' => '',
            ];
            return json_encode($ret);
        }

        $ret = [
            'test' =>'succesful'
        ];
        return json_encode($ret);      
    } 
    public function welcome(Request $request){
        $ret = [
            'test' =>'welcome succesful'
        ];
        return json_encode($ret);      
    } 
}
