<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\NoApi\FFMpegController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ConvertController extends ResponseController
{
    private $conv;

    public function __construct(){
        $this->conv = new FFMpegController;
    }

    public function convert(){

        $path = public_path('video.mp4');

        //$formats = null;

       // $convert = $this->conv->hlsConvertion($path);

       // $this->conv->hlsEncryptionAndConvertion($path,null,['240p']);

        //$this->conv->extractingImage($path, 'cover.jpg');
       // $this->conv->extractingAnimated_image($path, 'animated_image.gif');
        $data = $this->analyse();
        var_dump($data);

        //var_dump($convert);
    }

    public function analyse($path = 'video.mp4'){

        //$path = public_path('video.mp4');

            if(File::exists($path)){

                $path = public_path($path);
            }elseif(Storage::exists($path)){
                $path = storage_path($path);
            }else{
                return $this->errorResponse("error path", ['error' => 'invalid video path'], Response::HTTP_NOT_FOUND);
            }

        (object)$data = $this->conv->analyseVideo($path);

    }
}
