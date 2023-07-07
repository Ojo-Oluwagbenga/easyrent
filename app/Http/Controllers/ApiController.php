<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Product as ModelProduct;
use App\Models\Bins as ModelBin;
use App\Models\User as ModelUser;
use App\Models\Waitlist as ModelWaitlist;


class ApiController extends Controller
{
    public function manager(Request $request, $class_name, $func_name){
        
        $managedclasses = [
            'User' => (new User),
            // 'Bins' => (new Bin),
            'Waitlist' => (new Waitlist),
            'Product' => (new Product),
        ];
       
        try{           
            
            $tokenfromclient = $request->header('X-CSRF-TOKEN', 'default');
            $tokenfromserver = csrf_token();
            
            if (1==1){                                
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
        $this->send_mail();
        $ret = [
            'test' =>csrf_token()
        ];
        return json_encode($ret);      
    }
    public function pagetest(Request $request){
        return view('pagetest');      
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

    public function send_mail(){
        $to = "ojojohn2907@gmail.com";
        $subject = "Welcome to the real test world Ojo";
        $txt = "Hey Ojo lets drive our educational code into real world are you ready?";
        $headers = "From: myeasyrentonline@gmail.com" . "\r\n" .
        "CC: oneklapppa@gmail.com";

        mail($to,$subject,$txt,$headers);
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
        $data['likedproducts'] = '[]';       
        $data['role'] = '-';
        $data['name'] = '-';
        $data['gender'] = '-';

        $validator = Validator::make($data, [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:5', 'max:25'],
            'confirm_password' => ['required'],
        ]);

        if ($validator->fails()) {
            $ret = [
                'status' => 201,
                'data' => json_encode($validator->errors()->get('*')),
            ];
            return json_encode($ret);
        }

        if ($data['password'] !==  $data['confirm_password']){
            $ret = [
                'status' => 201,
                'message' => "The password and confirm password should match",
                'data' => '',
            ];
            return json_encode($ret);
        }
        

        // if ($data['email'] == 'admin@tuchdelta.com'){
        //     $data['role'] = 'admin';
        // }
        
        
        $user = [''];
        try{
            $user = ModelUser::where('email', $data['email'])->get();
        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'status' => 201,
                'message' => $ex->getMessage(),
                'data' => '',
            ];
            return json_encode($ret);
        }     
        
        if (count($user) !== 0){
            $ret = [
                'status' => 201,
                'message'=>'Email Already exists'
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
        $user->status = 0;

        try{
            $user->save();

            $user->code =  Util::Encode($user->id, 4, 'str');
            $user->save();
            //Send User Mail confirm mail


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
            'message'=> 'User successfully created',
            'data' => [
                'user' =>  $user->code,
            ],
        ];
        return json_encode($ret);
        
    }

    public function update($request){
        $data = $request->all();

        $updset = ($data['updset']);
        $querypair = ($data['querypair']);

        unset($updset['code']);
        unset($updset['email']);// Another code to 

        
        $updvalidator = [];

        foreach($updset as $key => $val){
            if (isset($this->valset[$key])){
                $updvalidator[$key] = $this->valset[$key];
            }else{
                unset($updset[$key]);
            }
        }

        $validator = Validator::make($updset, $updvalidator);
        if ($validator->fails()) {
            $ret = [
                'status' => '201',
                'reason' => 'valerror',
                'data' => json_encode($validator->errors()->get('*')),
            ];
            return json_encode($ret);
        }
        
        try{
            $user = ModelUser::where($querypair)->get(['code']);
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
                'data' => 'User not found',
            ];
            return json_encode($ret);
        }

        $user = ModelUser::where($querypair)->first();

        
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
                'user' => $user->code
            ],
        ];
        return json_encode($ret);
        
    }

    private function cleanArray($arr, $remove){

        $ret = [];
        $arr = array_diff($arr, $remove);
        foreach ($arr as $vals) {
            array_push($ret, $vals);
        }
        return ($ret);
    }

    public function fetch($request){
        $data = $request->all();

        $fetchset =  $data['fetchset'];
        $querypair =  $data['querypair'];

        $fetchset = $this->cleanArray($fetchset, ['id', 'password']);

        try{
            $model = ModelUser::select($fetchset)->where($querypair)->get();
            $ret = [
                'response' => '200',
                'data' => $model,
            ];
            return json_encode($ret);
        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'response' => '201',
                'data' => 'Invalid query',
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
            $user = ModelUser::where([
                    ['email', $data['email']], 
                    ['password', $data['password']] 
            ])->get(['code', 'name']);

            if (isset($user[0])){
                $user = $user[0];
            }else{
                $user = null;
            }
            

        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'status' => '201',
                'reason' => $ex->getMessage(),
                'data' => 'here',
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

class Waitlist{
    public function joinwaitlist($request){
        $data = $request->all();       
        
        $validator = Validator::make($data, [
            'name' => ['required', 'min:4', 'max:35', 'string'],
            'email' => ['required', 'email'],
            'message' => ['required', 'min:5', 'max:200'],
            'date' => ['required'],
        ]);

        if ($validator->fails()) {
            $ret = [
                'status' => 201,
                'data' => json_encode($validator->errors()->get('*')),
            ];
            return json_encode($ret);
        }
        
        
        $waitlist = [''];
        try{
            $user = ModelWaitlist::where('email', $data['email'])->get();
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
                    'email' => 'You are already on our waitlist'
                ],
            ];
            return json_encode($ret);
        }


        $waitlist = new ModelWaitlist;
        $waitlist->code = "Phold";
        $waitlist->name = $data['name'];
        $waitlist->email = $data['email'];
        $waitlist->message = $data['message'];        
        $waitlist->date = $data['date'];
        $waitlist->otherdata = $data['otherdata'];

        try{
            $waitlist->save();

            $waitlist->code =  Util::Encode($waitlist->id, 4, 'str');
            $waitlist->save();
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
                'waitlist_code' =>  $waitlist->code,
            ],
        ];
        return json_encode($ret);
        
    }

    public function fetchwaiters($request){
        $data = $request->all();
        $fetchset = $data['fetchset'];

        $fetchset = Util::cleanArray($fetchset, ['id', 'password']);

        try{
            $model = ModelWaitlist::select($fetchset)->get();
            $ret = [
                'response' => '200',
                'data' => $model,
            ];
            return json_encode($ret);
        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'response' => '201',
                'data' => 'Invalid query',
            ];
            return json_encode($ret);
        }
                
    }
}

class Product{


    private $valset =  [
        'name' => ['required', 'min:4', 'max:35', 'string'],
        'price' => ['required', 'numeric', 'min:0'],
        'description' => ['required', 'min:5'],
        'code' => ['required'],
        'type' => ['required'],
        'category' => ['required'],
        'imagepaths' => ['required'],
    ];
    

    public function create($request){
        
        $datapack = $request->all();


        $data = (array) json_decode($datapack['createset']);   
        $data['code'] = '-';
        $data['imagepaths'] = '[]';

        //Other data check
        $validator = Validator::make($data, $this->valset);
        if ($validator->fails()) {
            $ret = [
                'status' => 201,
                'data' => json_encode($validator->errors()->get('*')),
            ];
            return json_encode($ret);
        }        

        // File Check
        $updcount = $datapack['number_of_images'];
        $fileValidator = [];
        for ($i=0; $i < $updcount ; $i++) { 
            $fileValidator['file-' . ($i + 1)] = 'nullable|image|mimes:jpeg,jpg,png,gif';
        }
        $validator = Validator::make($datapack, $fileValidator);
        if ($validator->fails()) {
            $ret = [
                'status' => 201,
                'data' => json_encode($validator->errors()->get('*')),
            ];
            return json_encode($ret);
        }

        

        $model = new ModelProduct;
        $upldir = ""; // Upload directory

        foreach($data as $key => $val){
            $model->$key = $val;
        }

        try{
            $model->save();
            $mid = $model->id;
            try {
                for ($i=0; $i < $updcount; $i++) {
                    $file = $request->file('file-'. ($i+1));
                    if($file) {
                        // File extension
                        $extension = $file->getClientOriginalExtension();

                        $filename = "upload" . "-" . ($i+1) . "." . $extension;      

                        // File upload location
                        $upldir = 'uploadedfiles/productfiles/product_' . Util::Encode($mid, 4, 'str');
            
                        // Upload file
                        $file->move($upldir, $filename);
                        
                    }else{
                        // Response
                        $ret = [
                            'response' => 'failed',
                            'reason' => 'file-'. ($i+1) . ' not uploaded.',
                            'data' => '',
                        ];
                        return json_encode($ret);
                    }
                }      
            } catch(\Illuminate\Database\QueryException $ex){ 
                $ret = [
                    'status' => '201',
                    'reason' => $ex->getMessage(),
                    'data' => '',
                ];
                return json_encode($ret);
            }


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
                'product_code' =>  $model->code,
                'upload_dir' =>  $upldir,
            ],
        ];
        return json_encode($ret);
        
    }

    public function update($request){
        $data = $request->all();

        $updset = ($data['updset']);
        $querypair = ($data['querypair']);
        
        $updvalidator = [];

        foreach($updset as $key => $val){
            if (isset($this->valset[$key])){
                $updvalidator[$key] = $this->valset[$key];
            }else{
                unset($updset[$key]);
            }
        }

        $validator = Validator::make($updset, $updvalidator);
        if ($validator->fails()) {
            $ret = [
                'status' => '201',
                'reason' => 'valerror',
                'data' => json_encode($validator->errors()->get('*')),
            ];
            return json_encode($ret);
        }
        
        try{
            $product = ModelProduct::where($querypair)->get(['code']);
        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'status' => '201',
                'reason' => $ex->getMessage(),
                'data' => '',
            ];
            return json_encode($ret);
        }

        
        
        if (count($post) === 0){
            $ret = [
                'status' => '201',
                'data' => 'User not found',
            ];
            return json_encode($ret);
        }

        $product = ModelProduct::where($querypair)->first();

        
        foreach($updset as $key => $val){
            $product->$key = $val;
        }
        
        try{
            $product->save();
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
                'product_code' => $product->code
            ],
        ];
        return json_encode($ret);
        
    }

    private function cleanArray($arr, $remove){

        $ret = [];
        $arr = array_diff($arr, $remove);
        foreach ($arr as $vals) {
            array_push($ret, $vals);
        }
        return ($ret);
    }


    public function fetch($request){
        $data = $request->all();

        $fetchset =  $data['fetchset'];
        $querypair =  $data['querypair'];

        $fetchset = $this->cleanArray($fetchset, ['id', 'password']);

        try{
            $model = ModelUser::select($fetchset)->where($querypair)->get();
            $ret = [
                'response' => '200',
                'data' => $model,
            ];
            return json_encode($ret);
        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'response' => '201',
                'data' => 'Invalid query',
            ];
            return json_encode($ret);
        }
                
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
    public static  function cleanArray($arr, $remove){

        $ret = [];
        $arr = array_diff($arr, $remove);
        foreach ($arr as $vals) {
            array_push($ret, $vals);
        }
        return ($ret);
    }
}