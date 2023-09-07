<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Product as ModelProduct;
use App\Models\Bins as ModelBin;
use App\Models\User as ModelUser;
use App\Models\Waitlist as ModelWaitlist;
use Mail;
use Response;
use App\Mail\MailNotify;


class ApiController extends Controller
{
    public function manager(Request $request, $class_name, $func_name){
        
        $managedclasses = [
            'User' => (new User),
            'Waitlist' => (new Waitlist),
            'Product' => (new Product),
        ];
       
        try{           
            
            // $tokenfromclient = $request->header('X-CSRF-TOKEN', 'default');
            $tokenfromclient = $request->header('Authorization', 'default');


            $neglect = false;
            if ($class_name == 'user'){
                if ($func_name == 'create' || $func_name == 'login' || $func_name == 'validate_email'){
                    $neglect = true;
                }
            }

            if ('Bearer '.$request->session()->get("logged_mail") == $tokenfromclient || $neglect){
                $response = ($managedclasses[ucfirst($class_name)])->$func_name($request);
                return $response;
            }else{
                $ret = [
                    'status' => '400',
                    'reason' => 'Invalid Token',
                    'data' => 'No err',
                ];
                return Response::json($ret, 400); 
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
        
        // $data = [
        //     'subject'=>'This is the subject',
        //     'body'=>'Hello this is a freaking message from me!!!'
        // ];
        // $ret = [
        //     'test' =>csrf_token()
        // ];
        // Mail::to("ojojohn2907@gmail.com")->send(new MailNotify($data));
        // return json_encode($ret); 
        return Response::json([
            'test' => Tokener::create($request, ["email"=>"The freaking"], 'logged_mail'),
        ], 200); // Status code here
    }
    public function pagetest(Request $request){
        return view('pagetest');      
    }

    public function fetchtoken(Request $request, $apiaccesstoken){
        if ($apiaccesstoken != "alabi@easyrent"){
            return Response::json([
                'Message'=>"Access Not Allowed"
            ], 400);
        }

        $ret = [
            'token' => Tokener::create($request, ["email"=>"alabi@easyrent"], 'logged_mail'),
        ];
        return Response::json($ret, 200);
    }

    public static function send_mail($data){
        // $data = [
        //     'subject'=>'This is the subject',
        //     'body'=>'Hello this is a freaking message from me!!!',
        //     'receiver'=>'mail@receiver.com'
        // ];

        try {
            Mail::to($data['receiver'])->send(new MailNotify($data));
            return 'sent';
        } catch (\Throwable $th) {
            return 'not_sent';
        }
       

    }

}

class Tokener{
    public static function create($request, $data, $dataname){
        $text = Util::encodeWithKey(json_encode($data), 'kafkax');
        $request->session()->put($dataname, $text);
        return $text;
    }
    public static function read($request, $data){
        $data_text = Util::decodeWithKey($data, 'kafkax');
        $data = json_decode($data_text, true);
        return $data;
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
        $data['name'] = '-';
        $data['gender'] = '-';
        // return Response::json([
        //     'test' => Tokener::create($request, ["email"=>"The freaking"], 'logged_mail'),
        // ], 200);

        $validator = Validator::make($data, [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:5', 'max:25'], 
            'confirm_password' => ['required'],
            'role' => ['required'],
        ]);

        if ($validator->fails()) {
            $ret = [
                'status' => 201,
                'data' => json_encode($validator->errors()->get('*')),
            ];
            return Response::json($ret, 400);
        }

        if ($data['password'] !==  $data['confirm_password']){
            $ret = [
                'status' => 201,
                'message' => "The password and confirm password should match",
                'data' => '',
            ];
            return Response::json($ret, 400);  
        }
        
        $user = [''];
        try{
            $user = ModelUser::where('email', $data['email'])->get();
        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'status' => 0,
                'message' => $ex->getMessage(),
                'data' => '',
            ];
            return Response::json($ret, 500); 
        }     
        
        if (count($user) !== 0){
            $ret = [
                'status' => 201,
                'message'=>'Email Already exists'
            ];
            return Response::json($ret, 400); 
        }

        $user = new ModelUser;
        $user->name = $data['name'];
        $user->email = $data['email']."-";
        $user->password = $data['password'];        
        $user->gender = $data['gender'];
        $user->likedproducts = $data['likedproducts'];       
        $user->role = $data['role'];
        $user->code = $data['code'];
        $user->temp_email_code = $data['code'];
        $user->status = 0;
        $ret = 'not_sent';

        try{
            
            $user->save();

            $user->code =  Util::Encode($user->id, 4, 'str');
            $user->temp_email_code =  Util::Encode($user->id, 5, 'integer');
            $user->save();
            $mail_data = [
                'subject'=>'Mail confirmation',
                'code'=>$user->temp_email_code,
                'receiver'=>$data['email']
            ];
            $ret = ApiController::send_mail($mail_data);
            //Send User Mail confirm mail


        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'status' => 500,
                'reason' => $ex->getMessage(),
                'data' => '',
            ];
            return Response::json($ret, 200); 
        }
        
        $ret = [
            'token' => Tokener::create($request, ["email"=>$data['email']], 'logged_mail'),
            'status' => '200',
            'message'=> 'User successfully created',
            'data' => [
                'user' =>  $user->code,
                'mail_status'=> $ret
            ],
        ];
        return Response::json($ret, 201); 
        
    }

    public function validate_mail($request){
        $data = $request->all();

        
        $validator = Validator::make($data, [
            'email' => ['required'],
            'temp_code' => ['required'],
        ]);

        if ($validator->fails()) {
            $ret = [
                'status' => '400',
                'message' => 'Value error',
                'data' => json_encode($validator->errors()->get('*')),
            ];
            return Response::json($ret, 400); 
        }        
               
        
        try{
            $user = ModelUser::where([
                    ['email', $data['email']], 
                    ['temp_email_code', $data['temp_code']] 
            ])->get(['code', 'email']);


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
            return Response::json($ret, 500); 
        }
        

        if (!isset($user)){
            $ret = [
                'status' => '404',
                'message' => 'The temp code does not match the email user',
            ];
            return Response::json($ret, 400); 
        }


        $user->update(['status'=>1]); 
        $ret = [
            'token' => Tokener::create($request, ["email"=>$data['email']], 'logged_mail'),
            'user'=>$user,
            'data' => [
                'user' =>  $user['code'],
                'email' =>  $user['email']
            ],
        ];
        return Response::json($ret, 202); 
        
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
    
    public function login($request){
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
            return Response::json($ret, 400); 
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
                'message' => $ex->getMessage(),
                'data' => 'here',
            ];
            return Response::json($ret, 500); 
        }
        

        if (!isset($user)){
            $ret = [
                'message' => 'User not found',
            ];
            return Response::json($ret, 404);
        }


        $ret = [
            'token' => Tokener::create($request, ["email"=>$data['email']], 'logged_mail'),
            'response' => 'passed',
            'data' => [
                'user' =>  $user['code'],
                'name' =>  $user['name']
            ],
        ];
        return Response::json($ret, 202); 
        
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
        
        $data = $request->all();

        $data['code'] = '-';
        if (!isset($data['images'])){
            $data['images'] = '[]';
        }
        

        //Other data check
        $validator = Validator::make($data, $this->valset);
        if ($validator->fails()) {
            $ret = [
                'status' => 201,
                'data' => json_encode($validator->errors()->get('*')),
            ];
            return Response::json($ret, 500);
        }        

        // File Check
        $updcount = 0; // $datapack['number_of_images'];
        // $fileValidator = [];
        // for ($i=0; $i < $updcount ; $i++) { 
        //     $fileValidator['file-' . ($i + 1)] = 'nullable|image|mimes:jpeg,jpg,png,gif';
        // }
        // $validator = Validator::make($datapack, $fileValidator);
        // if ($validator->fails()) {
        //     $ret = [
        //         'status' => 201,
        //         'data' => json_encode($validator->errors()->get('*')),
        //     ];
        //     return json_encode($ret);
        // }

        

        $model = new ModelProduct;
        $upldir = ""; // Upload directory

        foreach($data as $key => $val){
            $model->$key = $val;
        }

        try{
            $model->save();
            $mid = $model->id;
            $model->code = Util::Encode($mid, 5, 'str');
            $model->save();

            try {
                //Returns Here
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
                'reason' => $ex->getMessage(),
                'data' => '',
            ];
            return Response::json($ret, 500);
        }
        
        $ret = [
            'status' => '200',
            'data' => [
                'product_code' =>  $model->code,
            ],
        ];
        return Response::json($ret, 200);
        
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

        $fetchset = $this->cleanArray($fetchset, ['id']);

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
            return Response::json($ret, 400);
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
        if ($type == 'integer'){
            $Res = '1234567890';
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

    public static function cleanArray($arr, $remove){

        $ret = [];
        $arr = array_diff($arr, $remove);
        foreach ($arr as $vals) {
            array_push($ret, $vals);
        }
        return ($ret);
    }

    public static function encodeWithKey($string, $key) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encryptedString = openssl_encrypt($string, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($iv . $encryptedString);
    }

    public static function decodeWithKey($encodedString, $key) {
        $encodedString = base64_decode($encodedString);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($encodedString, 0, $ivLength);
        $encryptedString = substr($encodedString, $ivLength);
        return openssl_decrypt($encryptedString, 'aes-256-cbc', $key, 0, $iv);
    }
}