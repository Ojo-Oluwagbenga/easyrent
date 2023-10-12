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


class Tokener{
    public static function create($request, $data, $dataname){
        $text = Util::encodeWithKey(json_encode($data), 'kafkax');
        // $request->session()->put($dataname, $text);
        return $text;
    }
    public static function read($data){
        $data_text = Util::decodeWithKey($data, 'kafkax');
        $data = json_decode($data_text, true);

        try {
            $ret = $data['email'];
            return $ret;
        } catch (\Throwable $th) {
            return false;
        }
    }
    public static function getuser($request){
        $tokenfromclient = $request->header('Authorization', 'default');
        
        if ($tokenfromclient == 'default'){
            return false;
        }
        $tarr = explode(" ", $tokenfromclient);
        if (!isset($tarr[1])){
            return false;
        }
        $ctext = $tarr[1];

        $data_text = Util::decodeWithKey($ctext, 'kafkax');
        $data = json_decode($data_text, true);

        try {
            $ret = $data['email'];
            return $ret;
        } catch (\Throwable $th) {
            return false;
        }
    }



}

class ProductController extends Controller{

    private $valset =  [
        'apartment' => ['required', 'min:4', 'max:100', 'string'],
        'amount' => ['required', 'numeric', 'min:0'],
        'code' => ['required'],
        'images' => ['required'],
    ];

    public function create($request){
        
        $data = $request->all();
        $ret = [
            'status' => 400,
            'Message' => "Invalid Token Sent!",
        ];

        $data['code'] = '-';
        if (!isset($data['images'])){
            $data['images'] = '[]';
        }

        $useremail = Tokener::getuser($request);
        if (!$useremail){
            return Response::json($ret, 400); 
        }
        
        $user = ModelUser::where(['email'=>$useremail])->first();
        if (!isset($user)){
            return Response::json($ret, 400);
        }
        $data['creator_code'] = $user->code;

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

        $useremail = Tokener::getuser($request);
        if (!$useremail){
            return Response::json($ret, 400); 
        }
        
        $user = ModelUser::where(['email'=>$useremail])->first();
        if (!isset($user)){
            $ret = [
                'status' => 400,
                'Message' => "Invalid Token Sent!",
            ];
            return Response::json($ret, 400);
        }
        $querypair['creator_code'] = $user->code;



        try{
            $model = ModelProduct::select($fetchset)->where($querypair)->get();
            $ret = [
                'response' => '200',
                'data' => $model,
            ];
            return json_encode($ret);
        }catch(\Illuminate\Database\QueryException $ex){ 
            $ret = [
                'response' => '201',
                'data' => 'Invalid query',
                'ex'=> $ex->getMessage(),
            ];
            return Response::json($ret, 400);
        }
                
    }
    public function fetchmyproducts(Request $request){
        $useremail = Tokener::getuser($request);
        if (!$useremail){
            return Response::json($ret, 400); 
        }
        
        $user = ModelUser::where(['email'=>$useremail])->first();
        if (!isset($user)){
            $ret = [
                'status' => 400,
                'Message' => "Invalid Token Sent!",
            ];
            return Response::json($ret, 400);
        }
        $querypair['creator_code'] = $user->code;



        try{
            $model = ModelProduct::select()->where($querypair)->get();
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
    public function fetchallproducts(Request $request){
        

        try{
            $model = ModelProduct::select()->get();
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