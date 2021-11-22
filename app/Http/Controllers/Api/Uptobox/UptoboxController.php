<?php

namespace App\Http\Controllers\Api\Uptobox;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UptoboxController extends Controller
{
    public function __construct()
    {
        $this->response = "";
        $this->apiLink = $this->defaultLink.'user/';
    }

    private $apiLink ;

    private $defaultLink = 'https://uptobox.com/api/';

    private $datas= [];

    private $response;


    private function setApiLink($link)
    {
        $this->apiLink = $this->apiLink.$link;
    }

    private function setDatas(array $datas)
    {
        $this->datas =  array_merge($this->datas, $datas);
    }

    private  function setDefaultLink($link)
    {
       $this->defaultLink .= $link;
    }

    public function getInfoAccount(){

        $this->setApiLink('me?token='.env('UPTOBOX_TOKEN'));

       $this->response = Http::withHeaders(['Accept'=>'application/json'])->get($this->apiLink);

        //var_dump($this->toObject());

        $resp = $this->response->object();

        if( $resp->statusCode == 0 ){

            if($resp->data->directDownload == 0 && $resp->data->premium != 0){

                $datas = $this->ableDirectDownloadLink();

                if($datas->statusCode == 0 ){

                    $resp->data->directDownload = $this->ableDirectDownloadLink()->data->directDownload;
                }
               
            }

            $resp->success = true;

            return $resp;

        }else{

            $resp->success = false;

            return $resp;

        }

    }

    public function ableDirectDownloadLink(){

        $this->setApiLink('settings');

        $this->setDatas([
            'token' => env('UPTOBOX_TOKEN'),
            'directDownload' => 1
          ]);

        return  Http::withHeaders(['Accept'=>'application/json'])->patch($this->apiLink, $this->datas)->object();

    }

    public function getFileInfo($fileCodes){

        $this->setApiLink('info?fileCodes='.$fileCodes);

        $this->response = Http::withHeaders(['Accept'=>'application/json'])->get($this->apiLink);

        $resp = $this->response->object();

        if( $resp->statusCode == 0 ){
        
            $resp->success = true;

            return $resp;

        }else{

            $resp->success = false;

            return $resp;

        }

    }

    public function listFileIntoFolder($path='//', $searchField=null,$search =null, $limit = '10', $orderBy = 'fld_id', $dir ='DESC',$offset=1){

        if (substr($path, 0,2) != '//') {
            $path = '//'.$path;
        }

        if (substr($path, 0,2) != './') {
            
            $path = substr($path, 1);

            $path = '//'.$path;
        }

        $this->setApiLink('files?token='.env('UPTOBOX_TOKEN'));

        if(!is_null($search)){

            $this->setApiLink('&search='.$search);

        }

        if(!is_null($searchField)){

            $this->setApiLink('&searchField='.$searchField); 

        }

        $this->setApiLink('&path='.$path.'&limit='.$limit.'&offset='.$offset.'&orderBy='.$orderBy.'&dir='.$dir);

        $this->response = Http::withHeaders(['Accept'=>'application/json'])->get($this->apiLink);

        $resp = $this->response->object();

        if( $resp->statusCode == 0 ){
        
            $resp->success = true;

            return $resp;

        }else{

            $resp->success = false;

            return $resp;

        }

    }

    public function updateFile($fileCodes, $name, $description){

        $this->setApiLink('files');

        $this->response = Http::withHeaders(['Accept'=>'application/json'])->patch($this->apiLink,
            [
                'token' => env('UPTOBOX_TOKEN'),
                'file_code' => $fileCodes,
                'new_name' => $name,
                'description' => $description,
            // 'password' => 'New password',
                'public' => 0,
            ]);

        $resp = $this->response->object();

        if( $resp->statusCode == 0 ){
        
            $resp->success = true;

            return $resp;

        }else{

            $resp->success = false;

            return $resp;

        }

    }

    /**
     * @param action = copy or move
     */

    public function moveOrCopyFile(array $file_id, $destination_fld_id, $action){

        $this->setApiLink('files');

            $listId = ',';

            foreach ($file_id as $value) {
                $listId .= $value.',';
            }

            $listId = substr($listId, 1);

            $datas = [
                'token' => env('UPTOBOX_TOKEN'),
                'file_codes' => $listId,
                'destination_fld_id' => $destination_fld_id,
                'action' => $action
            ];
        

        $this->response = Http::withHeaders(['Accept'=>'application/json'])->patch($this->apiLink,$datas);

        $resp = $this->response->object();

        if( $resp->statusCode == 0 ){
        
            $resp->success = true;

            return $resp;

        }else{

            $resp->success = false;

            return $resp;

        }

    }

        /**
     * @param action = copy or move
     */

    public function moveOrCopyFolder(array $folder_id, $destination_fld_id, $action){

        $this->setApiLink('files');

            $listId = ',';

            foreach ($folder_id as $value) {
                $listId .= $value.',';
            }

            $listId = substr($listId, 1);

            $datas = [
                'token' => env('UPTOBOX_TOKEN'),
                'fld_id' => $listId,
                'destination_fld_id' => $destination_fld_id,
                'action' => $action
            ];
        

        $this->response = Http::withHeaders(['Accept'=>'application/json'])->patch($this->apiLink,$datas);

        $resp = $this->response->object();

        if( $resp->statusCode == 0 ){
        
            $resp->success = true;

            return $resp;

        }else{

            $resp->success = false;

            return $resp;

        }

    }

    public function renameFolder($folder_id, $name){

        $this->setApiLink('files');

        $this->response = Http::withHeaders(['Accept'=>'application/json'])->patch($this->apiLink,[
            'token' => env('UPTOBOX_TOKEN'),
            'fld_id' => $folder_id,
            'new_name' => $name
        ]);

        $resp = $this->response->object();

        if( $resp->statusCode == 0 ){
        
            $resp->success = true;

            return $resp;

        }else{

            $resp->success = false;

            return $resp;

        }

    }

    public function createFolder($path, $name){

        $this->setApiLink('files');

        if (substr($path, 0,2) != '//') {
            $path = '//'.$path;
        }

        if (substr($path, 0,2) != './') {
            
            $path = substr($path, 1);

            $path = '//'.$path;
        }

        $this->response = Http::withHeaders(['Accept'=>'application/json'])->put($this->apiLink,[
            'token' => env('UPTOBOX_TOKEN'),
            'path' => $path,
            'name' => $name
        ]);

        $resp = $this->response->object();

        if( $resp->statusCode == 0 ){
        
            $resp->success = true;

            return $resp;

        }else{

            $resp->success = false;

            return $resp;

        }

    }

    public function deleteFile($file_id){

        $this->setApiLink('files');

        $listId = ',';

        foreach ($file_id as $value) {
            $listId .= $value.',';
        }

        $listId = substr($listId, 1);

        $this->response = Http::withHeaders(['Accept'=>'application/json'])->delete($this->apiLink,[
            'token' => env('UPTOBOX_TOKEN'),
            'file_codes' => $listId,
        ]);

        $resp = $this->response->object();

        if( $resp->statusCode == 0 ){
        
            $resp->success = true;

            return $resp;

        }else{

            $resp->success = false;

            return $resp;

        }

    }

    public function getUploadUrl(){

        $this->setDefaultLink('upload');

        $this->response = Http::withHeaders(['Accept'=>'application/json'])->get($this->defaultLink,[
            'token' => env('UPTOBOX_TOKEN'),
        ]);

        $resp = $this->response->object();

        if( $resp->statusCode == 0 ){
        
            $resp->success = true;

            return $resp;

        }else{

            $resp->success = false;

            return $resp;

        }
    }

}
