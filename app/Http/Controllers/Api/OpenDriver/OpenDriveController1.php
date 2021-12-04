<?php

namespace App\Http\Controllers\Api\OpenDriver;

use App\Http\Controllers\Api\ResponseController;
use App\Models\OpenDriver;
use Illuminate\Http\Request;
use Psr\Http\Message\ResponseInterface;
//use GuzzleHttp\Exception\RequestException;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Client\RequestException ;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OpenDriveController extends ResponseController
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



    public function __construct()
    {
        $this->sessionid = env('OD_SESSION')||false;
        $this->url = env('OD_URL');
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


        $result = $this->RunAPI("POST", "session/login.json", "login");
        if (!$result["success"])  return $result;

        $this->sessionid = $result["body"]["SessionID"];

        // save on the database
         $user =  $result["body"];

         $data = ["SessionID" => $user["SessionID"],
                    "UserName" => $user["UserName"],
                    "UserFirstName" => $user["UserFirstName"],
                    "UserLastName" => $user["UserLastName"],
                    "AccType" => $user["AccType"],
                    "UserLang" => $user["UserLang"],
            ];

        OpenDriver::updateOrCreate( ["UserName" =>  $user["UserName"]], $data);

        return $result;
    }

    private $options = [];

    private function option($type){

        switch ($type) {
            case 'open_file':
                return [
                        'json' => [
                            "session_id" => $this->sessionid,
                            "file_id" => (string)$this->OP_file["id"],
                            "file_size" => (string)$this->OP_file_to_upload["size"]
                         ]
                    ];
                break;
            case 'create_file':
                return [
                    'json' => [
                    "session_id" => $this->sessionid,
                    "folder_id" => (string)$this->OP_folder["id"],
                    "file_name" => (string)$this->OP_file_to_upload["name"],//$filename
                    "file_size" => (string)$this->OP_file_to_upload["size"],
                    "access_folder_id" => ""
                    ]
                ];
                break;

                case 'upload_file':

                    $pos = 0;
                    return [
                      'multipart' => [
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
                        'json' => [
                            "session_id" => $this->sessionid,
                            "file_id" => (string)$this->OP_file["id"],
                            "temp_location" => (string)$this->OP_file["templocation"],
                            "file_size" => (string)$this->OP_file_to_upload["size"],
                            "file_time" => (string)time(),
                            "access_folder_id" => ""
                        ]
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
                        'json' => [
                        "username" => $this->username,
                        "passwd" => $this->password,

                        "grand_type" => "password",
                        "client_id" => ""
                        ]
                    ];
                    break;

            default:

                $this->options['json']['session_id'] = $this->sessionid;

                return $this->options;

                break;
        }
    }

    private function process($method, $apipath, $options){

            $url = $this->url . $apipath;

        //$url = $this->url . $apipath;

        //$client = new Client();
        $client =  Http::withHeaders([
            'Accept' => 'application/json',
        ]);
        //$request = new GuzzleResq($method, $url,$options);

        //die(var_dump($options));




            //$res = $client->{strtolower($method)."Async"}($url, $options);

            $response = $client->{strtolower($method)}($url, $options);

            
            if( $response->successful() ){

                return [
                            "success" => true,
                                'body' => $response->json(),
                                "response" =>[
                                    'code' =>  $response->status()
                                ],

                        ];
                    
                return  $this->successResponse($response->json(), ['error' => 'request error'], $response->status());


            }elseif( $response->failed() ){

                return  $this->errorResponse($response->json(), ['error' => 'request error'], $response->status());

            }
       


    }

   /* public function GetFolderInfo($folderid = "0")
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        return $this->RunAPI("GET", "folder/list.json/" . $this->sessionid . "/" . $folderid);
    }
    */

    public function GetFolderList($folderid = "")
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        return $this->RunAPI("GET", "folder/list.json/" . $this->sessionid . "/" . $folderid);
    }

    public function GetObjectIDByName($folderid, $name)
    {
        $result = $this->GetFolderList($folderid);
        if (!$result["success"])  return $result;

        $info = false;

        if (isset($result["body"]["Folders"]))
        {
            foreach ($result["body"]["Folders"] as $info2)
            {
                if ($info2["Name"] === $name)  $info = $info2;
            }
        }

        if (isset($result["body"]["Files"]))
        {
            foreach ($result["body"]["Files"] as $info2)
            {
                if ($info2["Name"] === $name)  $info = $info2;
            }
        }

        return array("success" => true, "info" => $info);
    }

    public function CreateFolder($parent_folder_id, $name, $description = "", $is_public_mode = self::FOLDER_MODE_PRIVATE, $publicupload = false, $publicdisplay = false, $publicdownload = false)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        /*$this->OP_folder['name'] = $name;
        $this->OP_folder['parent_id'] = $parent_folder_id;
        $this->OP_folder['is_public'] = $is_public_mode;
        $this->OP_folder['public_upl'] = $publicupload;
        $this->OP_folder['public_display'] = $publicdisplay;
        $this->OP_folder['public_dnl'] = $publicdownload;
        $this->OP_folder['description'] = $description;
        */

        $this->options = [
            'json' => [
                'folder_name' => $name,
                'parent_id' => $parent_folder_id,
                'is_public' => $is_public_mode,
                'public_upl' => $publicupload,
                'public_display' => $publicdisplay,
                'public_dnl' => $publicdownload,
                'description' => $description,
            ]
        ];

        return $this->RunAPI("POST", "folder.json", 'create_folder');
    }

    public function CopyFolder($srcid, $destid)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        $this->options = [

            "json" =>[
                "session_id" => $this->sessionid,
                "folder_id" => (string)$srcid,
                "dst_folder_id" => (string)$destid,
                "move" => "false"
            ]

        ];

        return $this->RunAPI("POST", "folder/move_copy.json", 'move_copy');
    }

    public function MoveFolder($srcid, $destid)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        $this->options = array(
            "session_id" => $this->sessionid,
            "folder_id" => (string)$srcid,
            "dst_folder_id" => (string)$destid,
            "move" => "true"
        );

        return $this->RunAPI("POST", "folder/move_copy.json", 'move_copy');
    }

    public function RenameFolder($id, $newname)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        $this->options = array(
            "session_id" => $this->sessionid,
            "folder_id" => (string)$id,
            "folder_name" => (string)$newname
        );

        return $this->RunAPI("POST", "folder/rename.json", 'rename');
    }

    public function RemoveTrashedFolder($id)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        $options = array(
            "session_id" => $this->sessionid,
            "folder_id" => (string)$id
        );

        return $this->RunAPI("POST", "folder/remove.json", $options);
    }

    public function TrashFolder($id, $remove = false)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        $options = array(
            "session_id" => $this->sessionid,
            "folder_id" => (string)$id
        );

        $result = $this->RunAPI("POST", "folder/trash.json", $options);
        if (!$result["success"])  return $result;

        if ($remove)  $result = $this->RemoveTrashedFolder($id);

        return $result;
    }

    public function RestoreTrashedFolder($id)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        $options = array(
            "session_id" => $this->sessionid,
            "folder_id" => (string)$id
        );

        return $this->RunAPI("POST", "folder/restore.json", $options);
    }

    public function  UploadFile($path/*Request $request*/, $folderid = "NzBfMTE5NjQ5NF9BR2MxdQ" )
    {

        //if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        //if ($size === false)  $size = (is_resource($dataorfp) ? self::RawFileSize($dataorfp) : strlen($dataorfp));

       // $file = $request->file('file');
/*
        if(!$file){
            return $this->errorResponse( "error", "invalid file", 404);
        }*/

      /*  $this->OP_file_to_upload["path"] = $file->getRealPath();
        $this->OP_file_to_upload["name"] = $file->getClientOriginalName();
        $this->OP_file_to_upload["exten"] = $file->getClientOriginalExtension();
        $this->OP_file_to_upload["size"] = $file->getSize();
        $this->OP_file_to_upload["type"] = $file->getMimeType();
        */

        // $this->OP_file_to_upload["path"] = storage_path($path);
        // $this->OP_file_to_upload["name"] = File::name($path);
        // $this->OP_path_to_upload["exten"] = File::extension($path);
        // $this->OP_file_to_upload["size"] = File::size($path);
        // $this->OP_file_to_upload["type"] = File::mimeType($path);

        


        $this->OP_folder['id'] = $folderid;

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


            $result = $this->RunAPI("POST", "upload/create_file.json", 'create_file');

            if ($result["success"])  $this->OP_file["id"] = $result["body"]["FileId"];
            else
            {
                // Error code 409 means the file already exists.
                if (!isset($result["info"]) || !isset($result["info"]["response"]) || $result["info"]["response"]["code"] != 409)  return $result;

                // Try to find the existing file.
            }

        // Open the file.


        $result = $this->RunAPI("POST", "upload/open_file_upload.json", "open_file");

        if (!$result["success"])  return $result;

        $this->OP_file["templocation"] = $result["body"]["TempLocation"];


            $result = $this->RunAPI("POST", "upload/upload_file_chunk.json", "upload_file");

            if (!$result["success"])  return $result;

           /* if (is_callable($callback))  call_user_func_array($callback, array($fileid, $pos, strlen($data), $size));

            $pos += strlen($data);

            if (is_resource($dataorfp))  $data = @fread($dataorfp, 1048576);
            else  $data = "";
            */

        // Close the file.


        $result = $this->RunAPI("POST", "upload/close_file_upload.json", "close_file");
        if (!$result["success"])  return $result;

        $result["file_id"] = $this->OP_file["id"];
        $result["file_size"] = $this->OP_file_to_upload["size"];

        return $result;
    }

    public function DownloadFile__Internal($response, $body, &$opts)
    {
        fwrite($opts["fp"], $body);

        if (is_callable($opts["callback"]))  call_user_func_array($opts["callback"], array(&$opts));

        return true;
    }

    // Callback option only used when destination is a file.
    public function DownloadFile($destfileorfp, $fileid, $callback = false)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        if ($destfileorfp === false)  $options = array();
        else
        {
            $fp = (is_resource($destfileorfp) ? $destfileorfp : fopen($destfileorfp, "wb"));
            if ($fp === false)  return array("success" => false, "error" => self::OD_Translate("Invalid destination filename or handle."), "errorcode" => "invalid_filename_or_handle");

            $options = array(
                "read_body_callback" => array($this, "DownloadFile__Internal"),
                "read_body_callback_opts" => array("fp" => $fp, "fileid" => $fileid, "callback" => $callback)
            );
        }

        $result = $this->RunAPI("GET", "download/file.json/" . urlencode($fileid) . "?session_id=" . $this->sessionid, $options, 200, true, false);

        if ($destfileorfp !== false && !is_resource($destfileorfp))  fclose($fp);

        return $result;
    }

    public function GetThumbnail($file_id)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        return $this->RunAPI("GET", "file/thumb.json/" . urlencode($file_id) . "?session_id=" . $this->sessionid, array(), 200, true, false);
    }

    public function CopyFileToFolder($srcfileid, $destfolderid, $overwrite = true)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        $options = array(
            "session_id" => $this->sessionid,
            "src_file_id" => (string)$srcfileid,
            "dst_folder_id" => (string)$destfolderid,
            "move" => "false",
            "overwrite_if_exists" => ($overwrite ? "true" : "false"),
            "src_access_folder_id" => "",
            "dst_access_folder_id" => "",
        );

        return $this->RunAPI("POST", "file/move_copy.json", $options);
    }

    public function MoveFileToFolder($srcfileid, $destfolderid, $overwrite = true)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        $options = array(
            "session_id" => $this->sessionid,
            "src_file_id" => (string)$srcfileid,
            "dst_folder_id" => (string)$destfolderid,
            "move" => "true",
            "overwrite_if_exists" => ($overwrite ? "true" : "false"),
            "src_access_folder_id" => "",
            "dst_access_folder_id" => "",
        );

        return $this->RunAPI("POST", "file/move_copy.json", $options);
    }

    public function RenameFile($id, $newname)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        $options = array(
            "session_id" => $this->sessionid,
            "file_id" => (string)$id,
            "new_file_name" => (string)$newname,
            "access_folder_id" => ""
        );

        return $this->RunAPI("POST", "file/rename.json", $options);
    }

    public function RemoveTrashedFile($id)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        return $this->RunAPI("DELETE", "file.json/" . $this->sessionid . "/" . $id);
    }

    public function TrashFile($id, $remove = false)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        $options = array(
            "session_id" => $this->sessionid,
            "file_id" => (string)$id,
            "access_folder_id" => ""
        );

        $result = $this->RunAPI("POST", "file/trash.json", $options);
        if (!$result["success"])  return $result;

        if ($remove)  $result = $this->RemoveTrashedFile($id);

        return $result;
    }

    public function RestoreTrashedFile($id)
    {
        if ($this->sessionid === false)  return array("success" => false, "error" => self::OD_Translate("Not logged into OpenDrive."), "errorcode" => "no_login");

        $options = array(
            "session_id" => $this->sessionid,
            "file_id" => (string)$id
        );

        return $this->RunAPI("POST", "file/restore.json", $options);
    }

    public static function RawFileSize($fp)
    {
        $pos = 0;
        $size = 1073741824;
        fseek($fp, 0, SEEK_SET);
        while ($size > 1)
        {
            fseek($fp, $size, SEEK_CUR);

            if (fgetc($fp) === false)
            {
                fseek($fp, -$size, SEEK_CUR);
                $size = (int)($size / 2);
            }
            else
            {
                fseek($fp, -1, SEEK_CUR);
                $pos += $size;
            }
        }

        while (fgetc($fp) !== false)  $pos++;

        return $pos;
    }

    private static function OD_Translate()
    {
        $args = func_get_args();
        if (!count($args))  return "";

        return call_user_func_array("sprintf", $args);
    }

    private function dataRequestToSend($method, $options, $encodejson){

        $options = $this->option($options);


       /* $options2 = [];

        if ($method === "GET")
        {
            foreach ($options as $key => $val)  $options2[$key] = $val;
        }
        else
        {
            if ($encodejson)
            {

                //$options2["headers"] = array("Content-Type" => "application/json");

               if(isset($options["file_data"])){

                    $options2["multipart"] = [$options["file_data"]];

                    unset($options["file_data"]);
                }


                $options2["body"] = json_encode($options);

                //$options2["json"] = $options;

            }
            else
            {
                $options2 = array_merge($options2, $options);
            }
        }

        return $options2;*/
        return $options;

    }

    private function RunAPI($method, $apipath, $options = "", $encodejson = true, $decodebody = true)
    {
        /*$options2 = array(
            "method" => $method
        );
        */
       $options2 = $this->dataRequestToSend($method, $options, $encodejson);

        $retries = 1;

     // var_dump($options2);

        do
        {

            $result = $this->Process($method,$apipath, $options2);

          //  var_dump($options2);

           if (!$result["success"]){

                if ($result["response"]["code"] == 401) {

                    $this->Login();

                    $options2 = $this->dataRequestToSend($method, $options, $encodejson);

                } else {
                    sleep(1);
                }

            }

          //  var_dump($options2);

            $retries--;
        } while (!$result["success"] && $retries > 0);

        if (!$result["success"])  return $result;
       // if ($result["response"]["code"] != $expected)  return array("success" => false, "error" => self::OD_Translate("Expected a %d response from OpenDrive.  Received '%s'.", $expected, $result["response"]["line"]), "errorcode" => "unexpected_opendrive_response", "info" => $result);

        if ($decodebody)  $result["body"] = json_decode($result["body"], true);

        return $result;
    }
}
?>
