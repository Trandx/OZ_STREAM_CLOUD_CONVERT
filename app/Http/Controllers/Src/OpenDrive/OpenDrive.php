<?php

namespace App\Http\Controllers\Src\OpenDrive;

use App\Http\Controllers\Api\ResponseController;
use App\Models\OpenDriver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class OpenDrive extends ResponseController
{
    private $sessionid;

    const FOLDER_MODE_PRIVATE = 0;
    const FOLDER_MODE_PUBLIC = 1;
    const FOLDER_MODE_HIDDEN = 2;

    private $url = "";
    private $username = "";
    private $password = "";

    private $OP_folder = [];

    private $OP_file = [];

    private $OP_file_to_upload = [];

    private $_data;

    private $_response;

    public function __construct()
    {
        $this->sessionid = env('OD_SESSION')||false;
        $this->url = env('OD_URL',"https://dev.opendrive.com/api/v1/");
        $this->username = env('OD_USERNAME');
        $this->password = env('OD_PASSWORD');
        $getData =  OpenDriver::where("UserName",env('OD_USERNAME'))->first();
        $this->sessionid = isset($getData["SessionID"])?$getData["SessionID"]:$this->Login();
    }

    public function Login($username = null, $password = null)
    {

        if(!is_null($username)){
            $this->username =  $username;
        }

        if(!is_null($password)){
            $this->password = $password;
        }


        $this->RunAPI("POST", "session/login.json", "login");

        $this->sessionid = $this->_data["SessionID"];

        $data = ["SessionID" => $this->_data["SessionID"],
                "UserName" => $this->_data["UserName"],
                "UserFirstName" => $this->_data["UserFirstName"],
                "UserLastName" => $this->_data["UserLastName"],
                "AccType" => $this->_data["AccType"],
                "UserLang" => $this->_data["UserLang"],
        ];
        
        OpenDriver::updateOrCreate( ["UserName" =>  $data["UserName"]], $data);

        return $this;
    }

    public function response(){
       // header("Content-Type: application/json");
        return $this->_data ;
    }

    private  function _getResponse(){

        $response = $this->_response;

        $data = $response->json() ;

        if( $response->successful()){

            //var_dump($response->json());

            $this->_data =  [
                "success" => true,
                'datas' => $data,
                "code" =>  $response->status(),
            ];
                
          //  return  $this->successResponse($response->json(), ['error' => 'request error'], $response->status());

        }elseif( $response->failed() ){

            $this->_data["success"] = false;
            $this->_data["code"] =  $response->status();

            $this->_data = array_merge($this->_data, $response->json());

            // return [
            //     "success" => false,
            //     'datas' => $data['error']['message'],
            //     "code" =>  $response->status(),
            // ];

        }

    }

    private function makeError($msg){
        
        header("Content-Type: application/json");

        $error["success"] = false;
        $error["message"] = $msg;

        return $error ;
    }

    private $options = [];

    private function option($type){

        switch ($type) {
            case 'open_file':
                return [
                        "session_id" => $this->sessionid,
                        "file_id" => (string)$this->OP_file["id"],
                        "file_size" => (string)$this->OP_file_to_upload["size"]
                        
                    ];
                break;
            case 'create_file':
                return [
                    
                    "session_id" => $this->sessionid,
                    "folder_id" => (string)$this->OP_folder["id"],
                    "file_name" => (string)$this->OP_file_to_upload["name"],//$filename
                    "file_size" => (string)$this->OP_file_to_upload["size"],
                    "access_folder_id" => ""
                    
                ];
                break;

                case 'upload_file':

                    $pos = 0;
                    return [
                          [
                            "name" => "file_data",
                            "contents" => fopen($this->OP_file_to_upload["path"], 'r'),
                            "filename" => $this->OP_file_to_upload["name"],
                            "type" => "application/octet-stream",

                            'headers' => ['Content-Type' => 'application/binary'],

                            ],
                            /////////////////////////////////////
                            [
                                "name" => "session_id",
                                "contents" =>  $this->sessionid,
                            ],
                            [
                                "name" => "file_id",
                                "contents" =>  (string)$this->OP_file["id"],
                            ],
                            [
                                "name" => "temp_location",
                                "contents" =>  (string)$this->OP_file["templocation"],
                            ],
                            [
                                "name" => "chunk_offset",
                                "contents" =>  $pos,
                            ],
                            [
                                "name" => "chunk_size",
                                "contents" =>  $this->OP_file_to_upload["size"],
                            ],
                      /*  'progress' => function(
                            $downloadTotal,
                            $downloadedBytes,
                            $uploadTotal,
                            $uploadedBytes
                        ) {
                            echo $uploadedBytes;
                        },*/

                    ];
                    break;
                case 'close_file':
                    return [
                        
                            "session_id" => $this->sessionid,
                            "file_id" => (string)$this->OP_file["id"],
                            "temp_location" => (string)$this->OP_file["templocation"],
                            "file_size" => (string)$this->OP_file_to_upload["size"],
                            "file_time" => (string)time(),
                            "access_folder_id" => ""
                      
                    ];
                    break;
               /* case 'create_folder':
                    return  [
                        'json' => [
                        "session_id" => $this->sessionid,
                        "folder_name" => (string)$this->OP_folder['name'],
                        "folder_sub_parent" => (string)$this->OP_folder['parent_id'],
                        "folder_is_public" => (string)$this->OP_folder['is_public'],
                       /* "folder_public_upl" => (string)(int)$this->OP_folder['public_upl'],
                        "folder_public_display" => (string)(int)$this->OP_folder['public_display'],
                        "folder_public_dnl" => (string)(int)$this->OP_folder['public_dnl'],*/
                     /*   "folder_description" => (string)$this->OP_folder['description'],
                        ]
                    ];
                    break;*/

                case 'login':
                    return  [
                        
                        "username" => $this->username,
                        "passwd" => $this->password,

                        "grand_type" => "password",
                        "client_id" => ""
                        
                    ];
                    break;
            default:

                $this->options['session_id'] = $this->sessionid;

                if( isset($this->OP_folder['id']) ){ 

                    $this->options["folder_id"] = $this->OP_folder['id'] ;
                }

                return $this->options;

                break;
        }
    }

    private function RunAPI($method, $apipath, $options = ""){
       
        $options = $this->option($options);

       $retries = 5;

        do
        {

            $this->Process($method,$apipath, $options);

           if (!$this->_data["success"]){

                if ($this->_data["code"] == 401) {

                    $this->Login();

                    $options = $this->option($options);

                    $this->Process($method,$apipath, $options);

                } else {
                    sleep(1);
                }

            }

          //  var_dump($options2);

            $retries--;
        } while (!$this->_data["success"] && $retries > 0);

       // if ($result["response"]["code"] != $expected)  return array("success" => false, "error" => self::OD_Translate("Expected a %d response from OpenDrive.  Received '%s'.", $expected, $result["response"]["line"]), "errorcode" => "unexpected_opendrive_response", "info" => $result);

        //if ($decodebody)  $result = json_decode($result, true);

        return $this;

    }

    private function process($method, $apipath, $options){

        $url = $this->url . $apipath;

        //$url = $this->url . $apipath;

        //$client = new Client();
        $client =  Http::withHeaders([
            'Accept' => 'application/json',
        ])->withOptions(["verify"=>false])->asMultipart();
        //$request = new GuzzleResq($method, $url,$options);

        //die(var_dump($options));

        //$res = $client->{strtolower($method)."Async"}($url, $options);

        $this->_response = $client->{strtolower($method)}($url, $options);

        $this->_getResponse();

        return $this;

    }

    public function GetFolderList($folderid = "")
    {
        if($this->_data["success"] and $this->sessionid){

            $this->RunAPI("GET", "folder/list.json/" . $this->sessionid . "/" . $folderid);

        }

        return $this;
        
    }

    public function GetObjectIDByName($folderid, $name)
    {
        if($this->_data["success"] and $this->sessionid){

           $this->GetFolderList($folderid);

            $info = false;

            if (isset($this->_data["datas"]["Folders"]))
            {
                foreach ($this->_data["datas"]["Folders"] as $info2)
                {
                    if ($info2["Name"] === $name)  $info = $info2;
                }
            }

            if (isset($this->_data["datas"]["Files"]))
            {
                foreach ($this->_data["datas"]["Files"] as $info2)
                {
                    if ($info2["Name"] === $name)  $info = $info2;
                }
            }

             $this->_data["datas"] = $info;
        }

        return $this;
    }

    public function CreateFolder($name , $parent_folder_id ='/', $description = "", $is_public_mode = self::FOLDER_MODE_PRIVATE, $publicupload = false, $publicdisplay = false, $publicdownload = false)
    {
        if((is_null($this->_data) || $this->_data["success"]  ) and $this->sessionid){

            // if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

            /*$this->OP_folder['name'] = $name;
            $this->OP_folder['parent_id'] = $parent_folder_id;
            $this->OP_folder['is_public'] = $is_public_mode;
            $this->OP_folder['public_upl'] = $publicupload;
            $this->OP_folder['public_display'] = $publicdisplay;
            $this->OP_folder['public_dnl'] = $publicdownload;
            $this->OP_folder['description'] = $description;
            */

            $this->options = [
                    'folder_name' => $name,
                    'parent_id' => $parent_folder_id,
                    'is_public' => $is_public_mode,
                    'public_upl' => $publicupload,
                    'public_display' => $publicdisplay,
                    'public_dnl' => $publicdownload,
                    'description' => $description,
            ];

            $this->RunAPI("POST", "folder.json", 'create_folder');

            if($this->_data["success"]){
                $this->OP_folder['id'] = $this->_data['datas']['FolderID'];
            }

        }

        return $this;

    }

    public function CopyFolder($srcid, $destid)
    {
        if($this->_data["success"] and $this->sessionid){

            $this->options = [

                "json" =>[
                    "session_id" => $this->sessionid,
                    "folder_id" => (string)$srcid,
                    "dst_folder_id" => (string)$destid,
                    "move" => "false"
                ]

            ];

            $this->RunAPI("POST", "folder/move_copy.json", 'move_copy');

        }

        return $this;
    }

    public function MoveFolder($srcid, $destid)
    {
        if($this->_data["success"] and $this->sessionid){

            $this->OP_folder["id"] = (string)$srcid;

            $this->options = array(
                "dst_folder_id" => (string)$destid,
                "move" => "true"
            );

            $this->RunAPI("POST", "folder/move_copy.json", 'move_copy');
        }

        return $this;
    }

    public function RenameFolder($id, $newname)
    {
        if($this->_data["success"] and $this->sessionid){

            $this->OP_folder["id"] = (string)$id;

            $this->options = array(
                "folder_name" => (string)$newname
            );

            $this->RunAPI("POST", "folder/rename.json", 'rename');

        }

        return $this;
    }

    public function RemoveTrashedFolder($id)
    {
        if($this->_data["success"] and $this->sessionid){

            $this->OP_folder["id"] = (string)$id;

            // $this->options = array(
            //     "folder_id" => (string)$id
            // );

            $this->RunAPI("POST", "folder/remove.json", "");
        }
        
        return $this;
    }

    public function deleteFolder($id = null, $remove = false) // MOVE folder in the trash
    {
        if($this->_data["success"] and $this->sessionid){

            if($id){ 
                $this->OP_folder["id"] = (string)$id ;
            }

            // $this->options = array(
            //     "session_id" => $this->sessionid,
            //     "folder_id" => (string)$id
            // );

            $this->RunAPI("POST", "folder/trash.json", "");

            //var_dump($this->_response);


            if($remove){$this->RemoveTrashedFolder($id);}
        }

        return $this;
    }

    public function RestoreTrashedFolder($id)
    {
        if($this->_data["success"] and $this->sessionid){

            if($id){ $this->OP_folder["id"] = (string)$id; }

            // $options = array(
            //     "session_id" => $this->sessionid,
            //     "folder_id" => (string)$id
            // );

           $this->RunAPI("POST", "folder/restore.json", "");
        }

        return $this;

    }

    public function emptyTrash() // empty the trash
    {

        if($this->_data["success"] and $this->sessionid){

            $this->RunAPI("DELETE", "folder/trash.json/".$this->sessionid, "");
        }

        return $this;

    }


    public function  UploadFile($path/*Request $request*/, $folderid = null )
    {
        if($this->_data["success"] and $this->sessionid){

            

            $folderid?$this->OP_folder['id'] = $folderid:false;

            //var_dump($this->OP_folder);

            /*  $this->OP_file_to_upload["path"] = $file->getRealPath();
            $this->OP_file_to_upload["name"] = $file->getClientOriginalName();
            $this->OP_file_to_upload["exten"] = $file->getClientOriginalExtension();
            $this->OP_file_to_upload["size"] = $file->getSize();
            $this->OP_file_to_upload["type"] = $file->getMimeType();
            */

            $this->OP_file_to_upload["path"] = $path;
            $this->OP_file_to_upload["name"] = File::basename($path);
            $this->OP_path_to_upload["exten"] = File::extension($path);
            $this->OP_file_to_upload["size"] = File::size($path);
            $this->OP_file_to_upload["type"] = File::mimeType($path);

            /*if (is_resource($dataorfp))
            {
                @fseek($dataorfp, 0, SEEK_SET);
                $data = @fread($dataorfp, 1048576);
            }
            else
            {
                if ($size > strlen($dataorfp))  $size = strlen($dataorfp);
                $data = substr($dataorfp, 0, $size);
                $dataorfp = "";
            }*/


            // Create the file.


            $this->RunAPI("POST", "upload/create_file.json", 'create_file');

            if($this->_data["success"]){ $this->OP_file["id"] = $this->_data["datas"]["FileId"] ; }
            
            // Open the file.


            if($this->_data["success"]){ $this->RunAPI("POST", "upload/open_file_upload.json", "open_file") ; }


            if($this->_data["success"]){ $this->OP_file["templocation"] = $this->_data["datas"]["TempLocation"] ; }
        
            if($this->_data["success"]){ $this->RunAPI("POST", "upload/upload_file_chunk.json", "upload_file") ; }



            if($this->_data["success"]){ $this->RunAPI("POST", "upload/close_file_upload.json", "close_file") ; }
        

            if( $this->_data["success"] ){
                $this->_data["datas"]["file_id"] = $this->OP_file["id"];
                $this->_data["datas"]["file_size"] = $this->OP_file_to_upload["size"];
            }
           
        }
        return $this;
    }

    public function DownloadFile__Internal($response, $body, &$opts)
    {
        fwrite($opts["fp"], $body);

        if (is_callable($opts["callback"]))  call_user_func_array($opts["callback"], array(&$opts));

        return true;
    }

 
}