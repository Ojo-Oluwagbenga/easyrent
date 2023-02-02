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
            // 'Bin' => (new Bin),
            // 'Product' => (new Product),
        ];
       
        try{           
            
            $tokenfromclient = $request->header('X-CSRF-TOKEN', 'default');
            $tokenfromserver = csrf_token();
            
            if ($tokenfromclient === $tokenfromserver){                                
                $response = ($managedclasses[ucfirst($class_name)])->$func_name($request);
                return $response;
            }else{
                $ret = [
                    'status' => '201',
                    'reason' => 'Invalid Token',
                    'data' => 'No err',
                ];
                return json_encode($ret);
            }


        } catch (\Throwable $th) {
            $ret = [
                'status' => '201',
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
                'status' => '201',
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
    public function minitest(Request $request){
        $ret = [
            'test' =>csrf_token()
        ];
        return json_encode($ret);      
    } 
    public function fetchtoken(Request $request, $apiaccesstoken){
        $ret = [
            'status'=>201,
            'error' => [
                'code'=>"Invalid Api Access Code"
            ]
        ];
        if ($apiaccesstoken == "alabi@auth.tuchdelta"){
            $ret = [
                'status'=>201,
                'request_token' =>csrf_token()
            ];
        }        
        // e0wgtea3uzOBC7PPBBt5CiAcstS4TKdWOipZJC0h
        return json_encode($ret);      
    }
}

class User{

    private $valset =  [
        'name' => ['required', 'min:4', 'max:35', 'string'],
        'email' => ['required', 'email'],
        'password' => ['required', 'min:5', 'max:25'],
        'code' => ['required'],
        'gender' => ['required'],
        'institution' => ['required'],
        'role' => ['required'],
        'description' => ['min:10', 'max:255'],
    ];
    

    public function create($request){
        $data = $request->all();       
        $data['code'] = '-';       
        $data['likedproducts'] = '-';       
        $data['role'] = 'm';

        $validator = Validator::make($data, [
            'name' => ['required', 'min:4', 'max:35', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:5', 'max:25'],
            'code' => ['required'],
            'gender' => ['required'],
            'role' => ['required'],
        ]);

        if ($validator->fails()) {
            $ret = [
                'status' => 201,
                'data' => json_encode($validator->errors()->get('*')),
            ];
            return json_encode($ret);
        }
        

        if ($data['email'] == 'admin@tuchdelta.com'){
            $data['role'] = 'admin';
        }
        
        
        $user = [''];
        try{
            $user = ModelUser::where('email', $data['email'])->get();
        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'status' => 201,
                'reason' => $ex->getMessage(),
                'data' => '',
            ];
            return json_encode($ret);
        }     
        
        if (count($user) !== 0){
            $ret = [
                'status' => 201,
                'data' => [
                    'email' => 'Email Already exists'
                ],
            ];
            return json_encode($ret);
        }
        


        $user = new ModelUser;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['password'];        
        $user->gender = $data['gender'];
        $user->likedproducts = $data['likedproducts'];       
        $user->role = $data['role'];
        $user->code = $data['code'];

        try{
            $user->save();

            $user->code =  Util::Encode($user->id, 4, 'str');
            $user->save();
        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'status' => '201',
                'reason' => $ex->getMessage(),
                'data' => '',
            ];
            return json_encode($ret);
        }
        
        $ret = [
            'status' => '200',
            'data' => [
                'user' =>  $user->code,
            ],
        ];
        return json_encode($ret);
        
    }

    public function update($request){
        $data = $request->all();

        $updset = ($data['updset']);
        $updpair = ($data['updpair']);

        unset($updset['code']);
        unset($updset['email']);
        unset($updset['gender']);

        
        $updvaller = [];

        foreach($updset as $key => $val){
            if (isset($this->valset[$key])){
                $updvaller[$key] = $this->valset[$key];
            }else{
                unset($updset[$key]);
            }
        }

        $validator = Validator::make($updset, $updvaller);
        if ($validator->fails()) {
            $ret = [
                'status' => '201',
                'reason' => 'valerror',
                'data' => json_encode($validator->errors()->get('*')),
            ];
            return json_encode($ret);
        }
        
        try{
            $user = ModelUser::where($updpair[0] , $updpair[1])->get(['code']);
        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'status' => '201',
                'reason' => $ex->getMessage(),
                'data' => '',
            ];
            return json_encode($ret);
        }

        
        
        if (count($user) === 0){
            $ret = [
                'status' => '201',
                'reason' => 'valerrorpop',
                'data' => 'User not found',
            ];
            return json_encode($ret);
        }

        $user = ModelUser::where([$updpair[0] => $updpair[1]])->first();

        
        foreach($updset as $key => $val){
            $user->$key = $val;
        }
        
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
            'response' => 'passed',
            'data' => [
                'user' =>   $user->code
            ],
        ];
        return json_encode($ret);
        
    }

    public function fetch($request){
        $data = $request->all();

        $fetchset =  $data['fetchset'];
        $querypair =  $data['querypair'];
        
        try{
            $model = ModelUser::select($fetchset)->where($querypair)->get();
            $ret = [
                'response' => '200',
                'data' => json_encode($model),
            ];
            return json_encode($ret);
        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'response' => '201',
                'reason' => $ex->getMessage(),
                'data' => '',
            ];
            return json_encode($ret);
        }
                
    }
    
    public function validate($request){
        $data = $request->all();

        $validator = Validator::make($data, [
            'email' => ['required'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            $ret = [
                'status' => '201',
                'reason' => 'Value error',
                'data' => json_encode($validator->errors()->get('*')),
            ];
            return json_encode($ret); 
        }        

        
        
        try{
            $user = ModelUser::select(['code', 'name'])->where([
                                                ['email', $data['email']], 
                                                ['password', $data['password']] 
            ]);

        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'status' => '201',
                'reason' => $ex->getMessage(),
                'data' => '',
            ];
            return json_encode($ret);
        }
        

        if (!isset($user)){
            $ret = [
                'status' => '201',
                'data' => 'User not found',
            ];
            return json_encode($ret);
        }

        $ret = [
            'response' => 'passed',
            'data' => [
                'user' =>  $user['code'],
                'name' =>  $user['name']
            ],
        ];
        return json_encode($ret);
        
    }
    
    
    
}


class Util{
    public static function Encode($code, $encNum, $type){
        $join = '';
        for ($i = 0; $i < $encNum - strlen($code); $i++) {
            $join .= '0';
        }
        $code = $join . $code;

        $Res = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        if ($type == 'str'){
            $Res = 'ZgBoFklNOaJKLM5XYh12pqr6wQRSTdefijAPbcU4mnVW0stuv78xyzGCDE3HI9';
        }        
        $tlenght = strlen($Res);
        $rtl = '';
        for ($i = 0; $i < strlen($code); $i++) {
            $el = $code[$i];
            $k = (strpos($Res, $el) + $encNum + $i) % $tlenght;
            $rtl .=  substr($Res, $k, 1);
        }
        return $rtl;
    }
    public static function Decode($code, $encNum, $type){
        $Res = 'ZgBoFklNOaJKLM5XYh12pqr6wQRSTdefijAPbcU4mnVW0stuv78xyzGCDE3HI9';
        if ($type == 'int'){
            $Res = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        }  
        $tlenght = strlen($Res);
        $rtl = '';
        for ($i = 0; $i < strlen($code); $i++) {
            $el = $code[$i];
            $k = (strpos($Res, $el) - $encNum - $i + $tlenght) % $tlenght;
            $rtl .=  substr($Res, $k, 1);
        }
        return $rtl;
    }
}