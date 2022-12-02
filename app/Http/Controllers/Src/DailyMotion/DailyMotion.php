<?php

namespace App\Http\Controllers\Src\DailyMotion;


use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function GuzzleHttp\json_decode;

class DailyMotion
{
    public function __construct()
    {
        $this->_credentiels();

    }

    public $_client_id;

    public $_client_secret;

    public $_grant_type;

    public $_username;

    public $_password;

    public $_grat_type;

    private $_uri;

    private $_url;

    private $_response;

    private $_access_token = null;

    private $_token_type = null;

    private $_expires_in;

    private $_refresh_token = null;

    private $_scope = "manage_videos";

    private $_uid;

    private $_upload_url;

    private $_progress_url;

    private $_data_url;

    private $_data;

    private function _credentiels(){

        $this->_client_id = env("DAILYMOTION_CLIENT_ID");

        $this->_client_secret = env("DAILYMOTION_CLIENT_SECRET");

        $this->_username = env("DAILYMOTION_USERNAME");

        $this->_password = env("DAILYMOTION_PASSWORD");

        $this->_grant_type = env("DAILYMOTION_GRANT_TYPE_PASSWORD", "DAILYMOTION_GRANT_TYPE_SERVER", );

        $this->_uri=env("DAILYMOTION_URI");

        $this->_url = $this->_url();
    }

    private function _url(){
        return (object) [
            "auth" => $this->_uri."oauth/token",
            "file_upload" => $this->_uri."file/upload",
            "join_url" => $this->_uri."me/videos", //"user/x2pp5x2/videos",
            "update" => $this->_uri."user/x2pp5x2/videos",
        ];
    }

    public function response(){
        header("Content-Type: application/json");
        return $this->_data ;
    }

    private  function _getResponse(){

        $response = $this->_response;

        $data = $response->json() ;

        //die(response($data["error"]));
        if( $response->successful() and !isset($data["error"])){

            //var_dump($response->json());

            foreach ($data as $key => $value) {

                if($key == "url"){

                    $this->_data_url = $value;

                }else{

                    $this->{"_".$key} = $value;

                }


            }

            $this->_data = [
                "success" => true,
                'datas' => $data,
                //"code" =>  $response->status(), //return always 200 status
            ];

          //  $this->_response =  $this->successResponse($response->json(), ['error' => 'request error'], $response->status());

        }else{

            $this->_data["success"] = false;

            $this->_data = array_merge($this->_data, $response->json());

            // header("Content-Type: application/json");

            // die(json_encode($this->_data));

            
        }

    }

    private function makeError($msg){
        
        header("Content-Type: application/json");

        $error["success"] = false;
        $error["message"] = $msg;

        return $error ;
    }

    public function login (){

        $data = [
            "client_id" => $this->_client_id,
            "client_secret" => $this->_client_secret,
            "grant_type" => $this->_grant_type,
        ];

        if($this->_grant_type == "password"){
            $data =  array_merge($data, [
                "username" =>  $this->_username,
                "password" =>  $this->_password,
            ]);
        }

        $this->_response = Http::acceptJson()->asForm()->post($this->_url->auth, $data);

        // {
        //     "access_token": "Ym5gQhoLT1R0TEcmA30QBGsDOwQQXg14DQEUIA",
        //     "token_type": "Bearer",
        //     "expires_in": 36000,
        //     "refresh_token": null,
        //     "scope": "",
        //     "uid": null
        // }

        $this->_getResponse();

        return $this;
    }

    public function getUrl(){

        if($this->_data["success"] and $this->_access_token){

            $this->_response = Http::withToken($this->_access_token."h",  $this->_token_type)->get($this->_url->file_upload);

            // {
            //     "upload_url": "https://upload-01.dc3.dailymotion.com/upload?uuid=2106250d4cdef562a2705554364952cb&seal=0b9fec067c1d862111d50a327d429be5",
            //     "progress_url": "https://upload-01.dc3.dailymotion.com/progress?uuid=2106250d4cdef562a2705554364952cb"
            // }

            $this->_getResponse();

        }

        

        return $this;

        //$this->makeError("undefined token");


    }

    public function sendFile($path){

        /// get url

        if($this->_data["success"] and $this->_access_token){

            if($this->_upload_url){

                //die(var_dump($file));

                $data = [
                    [
                        "name" => "file",
                        "contents" => fopen( $path, 'r'),

                    ],
                ];

                $this->_response = Http::asMultipart() //withHeaders([ 'Content-Type' => 'multipart/form-data' ])
                ->withToken($this->_access_token,  $this->_token_type)
                ->withOptions(["verify"=>false])
                //->attach('files', $file) //$request->file('file')
                ->post($this->_upload_url, $data);

                $this->_getResponse();

                //unlink($path); // delete original file

                
            }

            //$this->makeError("undefined upload url");
        }

       // $this->makeError("undefined token");
       return $this;
    }

    public function convertProgress(){


        if(!$this->_data["success"] and $this->_access_token){

            if($this->_progress_url){

                $this->_response = Http::withToken($this->_access_token,  $this->_token_type)
                ->get($this->_progress_url);

                $this->_getResponse();


                
            }

            //$this->makeError("undefined progress url");


        }

        //$this->makeError("undefined token");
        return $this;
    }

    public function joinFileToData($data){

        if($this->_data["success"] and $this->_access_token){

            if($this->_upload_url){

                $data["url"] = $this->_data_url;
               // $data["scopes"] = $this->_scope;

                //var_dump($this->_url->join_url);

                $this->_response = Http::asMultipart()
                    ->withToken($this->_access_token,  $this->_token_type)
                    ->withOptions(["verify"=>false])
                    ->post($this->_url->join_url, $data);

                    $this->_getResponse();

                
            }

           // $this->makeError("undefined upload url");

        }

        //$this->makeError("undefined token");
        return $this;

    }

    public function uploadFile($data){

        //var_dump($data->details);

        if(!isset($data->path) or !isset($data->details)){

           return $this->makeError("path or details is not set");

        }

        if(!File::exists($data->path)){
            return $this->makeError("file doesn't exist");
        }

        $this->login()->getUrl()->sendFile($data->path);

        // $response = (object) $this->convertProgress()->response();

        // while ($response->success and ) {
        //     # code...
        // }
        // if()


        return $this->joinFileToData($data->details)->response();
    }

}
