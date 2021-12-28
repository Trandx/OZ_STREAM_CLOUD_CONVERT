<?php

namespace App\Http\Controllers\NoApi;

use App\Http\Controllers\Api\CustomCloudController;
use App\Http\Controllers\Controller;
use App\Jobs\ConverMediasJob;
use App\Jobs\OpendDriveUploadJob;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Streaming\Representation;
use Streaming\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Coordinate\Dimension;
use getID3;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class FFMpegController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private function manualGenerateRepresentation (){

        $this->r_144p  = (new Representation)->setKiloBitrate(95)->setResize(256, 144);
        $this->r_240p  = (new Representation)->setKiloBitrate(150)->setResize(426, 240);
        $this->r_360p  = (new Representation)->setKiloBitrate(276)->setResize(640, 360);
        $this->r_480p  = (new Representation)->setKiloBitrate(750)->setResize(854, 480);
        $this->r_720p  = (new Representation)->setKiloBitrate(2048)->setResize(1280, 720);
        //$this->r_1080p = (new Representation)->setKiloBitrate(4096)->setResize(1920, 1080);
        //$this->r_2k    = (new Representation)->setKiloBitrate(6144)->setResize(2560, 1440);
        //$this->r_4k    = (new Representation)->setKiloBitrate(17408)->setResize(3840, 2160);

    }

    public function __construct(){

        $this->initializeConfig();
        $this->manualGenerateRepresentation();

    }

    private function initializeConfig(){
        $this->config = [
            'ffmpeg.binaries'  => exec('which ffmpeg'),//'/usr/bin/ffmpeg',
            'ffprobe.binaries' => exec('which ffprobe'), //'/usr/bin/ffprobe',
            'timeout'          => 0,//3600*2,//3600*3, // The timeout for the underlying process
            'ffmpeg.threads'   => 5,   // The number of threads that FFmpeg should use
        ];

        $this->log = new Logger('FFmpeg_Streaming');
        $this->log->pushHandler(new StreamHandler(public_path('logs/ffmpeg-streaming.log'))); // path to log file
    }


    private $ffmeg;
    private $config;
    private $log;
    private $r_144p ;
    private $r_240p ;
    private $r_360p ;
    private $r_480p ;
    private $r_720p ;
    private $r_1080p;
    private $r_2k   ;
    private $r_4k   ;

    public function hlsConvertion($path, array $formats = null, array $option = null){

        $custom = new CustomCloudController();

        if(File::exists($path)){

            $path = public_path($path);
            $filename = File::name($path);

        }elseif(Storage::exists($path)){

            $path = Storage::path($path);
            $filename = File::name($path);

        }else{

            return $this->errorResponse("error path", ['error' => 'invalid video path'], Response::HTTP_NOT_FOUND);

        }

        $dir = dirname($path);


       /* if(!is_dir($dir)){
            mkdir($dir);
        }*/

        if($formats){

            foreach ($formats as $value) {
                $value = 'r_'.$value;
                $convertTo[]= $this->{$value};
            }

        }else{

            $convertTo = [$this->r_144p, $this->r_240p, $this->r_360p, $this->r_480p,
                      $this->r_720p, /*$this->r_1080p, $this->r_2k, $this->r_4k*/];

        }

        //$fileDir = $dir.'/converted';

        $fileDir = $dir;

        /* $from_custom = [
                'cloud' => $custom,
                'options' =>  [$fileDir] //this array will be passed to the `download` method in the `CustomCloud` class.
            ];
            */
    
            $to_custom = [
                'cloud' => $custom,
                'options' => $option? array_merge($option, ["finalFolder" => $fileDir]):["finalFolder" => $fileDir]   //this array will be passed to the `upload` method in the `CustomCloud` class.
            ];

        $this->ffmpeg = FFMpeg::create($this->config, $this->log)->open($path)->hls()
        ->x264()
        ->addRepresentations($convertTo)
        //->save($fileDir);
        ->save(null /*$fileDir.'/'.$filename*/, $to_custom);
        /*->autoGenerateRepresentations([720, 360]) // You can limit the number of representatons
        ->save();*/
    }

    public function hlsEncryptionAndConvertion($path, $keyPath = null, array $formats = null, array $option = null){

        $custom = new CustomCloudController();


        if(File::exists($path)){

            $path = public_path($path);
            $filename = File::name($path);

        }elseif(Storage::exists($path)){

            $path = Storage::path($path);
            $filename = File::name($path);

        }else{

            return $this->errorResponse("error path", ['error' => 'invalid video path'], Response::HTTP_NOT_FOUND);

        }

        $dir = dirname($path);


       /* if(!is_dir($dir)){
            mkdir($dir);
        }*/

        if($formats){

            foreach ($formats as $value) {
                $value = 'r_'.$value;
                $convertTo[]= $this->{$value};
            }


        }else{

            $convertTo = [$this->r_144p, $this->r_240p, $this->r_360p, $this->r_480p,
                      $this->r_720p, /*$this->r_1080p, $this->r_2k, $this->r_4k*/];

        }

            //A path you want to save a random key to your local machine

        $uriKey = time().'.key';
        $save_to = $keyPath?? storage_path('keys/'.$uriKey);

            //An URL (or a path) to access the key on your website

            /// write one route that redirect on expired link

       // $url = env('PUBLIC_APP_URL').'/'.$uriKey;

        $url = $uriKey;

            // or $url = '/"PATH TO THE KEY DIRECTORY"/key';
             //$fileDir = $dir.'/converted';
            $fileDir = $dir;

            /* $from_custom = [
                    'cloud' => $custom,
                    'options' =>  [$fileDir] //this array will be passed to the `download` method in the `CustomCloud` class.
                ];
                */
        
                $to_custom = [
                    'cloud' => $custom,
                    'options' => $option? array_merge($option, ["finalFolder" => $fileDir]):["finalFolder" => $fileDir]   //this array will be passed to the `upload` method in the `CustomCloud` class.
                ];

            $this->ffmpeg = FFMpeg::create($this->config, $this->log)->open($path)->hls()
            ->encryption($save_to, $url)
            ->x264()
            ->addRepresentations($convertTo)
            ->save(null /*$fileDir.'/'.$filename*/, $to_custom);

        // foreach ($formats as $key => $val) {
        //    $this->ffmpeg = FFMpeg::create($this->config, $this->log)->open($path)->hls()
        //     ->encryption($save_to, $url)
        //     ->x264()
        //     ->addRepresentations([$convertTo[$key]])
        //     ->save($dir.'/converted/'.$val.'/'.$filename, );
        // }
        
        
        

        /*->autoGenerateRepresentations([720, 360]) // You can limit the number of representatons
        ->save();*/

        //ConverMediasJob::dispatch($dir.'/converted')->delay(now()->addSeconds(5));

        
    }

    public function extractingImage($path, $path_out){

         $this->ffmpeg = FFMpeg::create($this->config, $this->log)->open($path)->frame(TimeCode::fromSeconds(10), new Dimension(854, 480))
                ->save($path_out); //poster.jpg

    }

    public function extractingAnimated_image($path, $path_out){

        $this->ffmpeg = FFMpeg::create($this->config, $this->log)->open($path)->gif(TimeCode::fromSeconds(5), new Dimension(854, 480), 5)
                ->save($path_out); //'animated_image.gif'

    }

    public function analyseVideo($path){

        $getID3 = new getID3;
        $this->dataAnalyseVideo = $getID3->analyze($path);

        if(isset($this->dataAnalyseVideo['playtime_string'])){

        $data['duration'] =  $this->dataAnalyseVideo['playtime_string'];

        }

        $data['r_x'] =  $this->dataAnalyseVideo['video']['resolution_x'];
        $data['r_y'] =  $this->dataAnalyseVideo['video']['resolution_y'];
        $data['size'] =  $this->dataAnalyseVideo['filesize'];

       return  $data;
    }


}
