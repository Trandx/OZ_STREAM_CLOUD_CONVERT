<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\NoApi\FFMpegController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class ConvertController extends ResponseController
{
    private $conv;

    public function __construct(){
        $this->conv = new FFMpegController;
    }

    public function convert($path = 'public/video.mp4', array $formatList = [], array $option = null){

       // $path = storage_path('video.mp4');

       //var_dump($option);
        
        if (empty($formatList) ) {
              
            //$formats = null;

        // $convert = $this->conv->hlsConvertion($path);

        // $this->conv->hlsEncryptionAndConvertion($path,null,['240p']);

            //$this->conv->extractingImage($path, 'cover.jpg');
        // $this->conv->extractingAnimated_image($path, 'animated_image.gif');

            $data = $this->analyse($path);

           if(!isset($data->r_y)){
            return $this->errorResponse("error path", ['error' => 'invalid video path'], Response::HTTP_NOT_FOUND);
           }
                if ($data->r_y >= 720 ) {
                    //plage du 720p
                    //$formatList = ['720p','480p', '360p','240p', '144p'];

                    $formatList = ['720p', '360p', '144p'];
                
                }

                if ($data->r_y < 720 and $data->r_y >= 480) {
                // plage du 480p
                //$formatList = ['480p', '360p','240p', '144p'];

                $formatList = ['360p', '144p'];
                
                }

                if ($data->r_y < 480 and $data->r_y >= 360) {
                // plage de 360p
                    //$formatList = ['480p', '360p','240p', '144p'];
                //$formatList = ['360p', '144p'];

                $formatList = ['144p'];
                }

                if ($data->r_y < 360  and $data->r_y >= 240) {
                    // plage 243p
                    //$formatList = ['240p', '144p'];
                    $formatList = [ '144p'];
                
                }

                if ($data->r_y < 240) {
                    //plage de 144p
                    $formatList = [ '144p'];
                
                }

        }

       //return $this->conv->hlsConvertion($path,$formatList, $option);

       return $this->conv->hlsEncryptionAndConvertion($path,null,$formatList, $option);
       
    }

    public function analyse($path = 'public/video.mp4'){

        //$path = public_path('video.mp4');

         //echo public_path($path);
        // $path = storage_path($path);

            if(File::exists($path)){

                $path = public_path($path);

            }elseif(Storage::exists($path)){

                $path = Storage::path($path);

            }else{

                return $this->errorResponse("error path", ['error' => 'invalid video path'], Response::HTTP_NOT_FOUND);

            }

        return (object)$this->conv->analyseVideo($path);

    }
}
