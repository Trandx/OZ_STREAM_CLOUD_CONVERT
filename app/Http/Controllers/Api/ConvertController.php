<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\NoApi\FFMpegController_convert_v2;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class ConvertController extends ResponseController
{
    private $conv;

    public function __construct($path = null){
        $this->conv = new FFMpegController_convert_v2;
        $this->path = $path;
    }

    public $path;

    public $formatList = null;

    private $fileConvertion;

    public function path($path){
        $this->path = $path;
        return $this;
    }

    public function convert(){

        if(!File::exists($this->path)){

            return $this->errorResponse("error path", ['error' => 'invalid video path'], Response::HTTP_NOT_FOUND);

        }

        if(!$this->formatList){
            $this->createFormat();
        }

        $this->fileConvertion = $this->conv->convert($this->path, $this->formatList);
        
       return $this;
    //    return $this->conv->hlsConvertion($path,$formatList, $option);

       //return $this->conv->hlsEncryptionAndConvertion($path,null,$formatList, $option);
       
    }

    public function save($path=null){

       
       return $this->fileConvertion->save($path);

    }

    public function createFormat(){
              
            //$formats = null;

            // $convert = $this->conv->hlsConvertion($path);

            // $this->conv->hlsEncryptionAndConvertion($path,null,['240p']);

                //$this->conv->extractingImage($path, 'cover.jpg');
            // $this->conv->extractingAnimated_image($path, 'animated_image.gif');

        $data = $this->analyse();

        if(!isset($data->r_y)){
            return $this->errorResponse("error path", ['error' => 'invalid video path'], Response::HTTP_NOT_FOUND);
        }
        if ($data->r_y >= 720 ) {
            //plage du 720p
            //$this->formatList = ['720p','480p', '360p','240p', '144p'];

            $this->formatList = ['720p', '360p', '144p'];
        
        }

        if ($data->r_y < 720 and $data->r_y >= 480) {
            // plage du 480p
            //$this->formatList = ['480p', '360p','240p', '144p'];

            $this->formatList = ['360p', '144p'];
        
        }

        if ($data->r_y < 480 and $data->r_y >= 360) {
        // plage de 360p
            //$this->formatList = ['480p', '360p','240p', '144p'];
        $this->formatList = ['360p', '144p'];
        }

        if ($data->r_y < 360  and $data->r_y >= 240) {
            // plage 243p
            //$this->formatList = ['240p', '144p'];
            $this->formatList = [ '144p'];
        
        }

        if ($data->r_y < 240) {
            //plage de 144p
            $this->formatList = [ '144p'];
        
        }

        return $this;
    }

    public function analyse($path = null){

        //$path = 'public/video.mp4'
        //$path = public_path('video.mp4');

         //echo public_path($path);
        // $path = storage_path($path);

        return (object)$this->conv->analyseVideo($path?? $this->path);

    }
}
