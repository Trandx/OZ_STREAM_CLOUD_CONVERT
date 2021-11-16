<?php

namespace App\Http\Controllers;

set_time_limit(0);

use App\Http\Controllers\Api\ResponseController;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
//use FFMpeg\FFMpeg as FFMpegFFMpeg;
use FFMpeg\Filters\Video\VideoFilters;
use FFMpeg\Format\Video\X264;
use FFMpeg\Media\Gif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\FFMpeg\CopyFormat;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use ProtoneMedia\LaravelFFMpeg\Exporters\HLSExporter;

class FfmpegController extends ResponseController
{
    private static $fileName="video.mp4";
    private static $convertFilePath;
    public static $defaultPath;
    public $ffmpeg;

    private $bitrage= [
        "144p" => 250, //250
        "240p" => 350, //350 
        "360p" => 500, //350 – 800 
        "480p" => 800,//800 – 1200
        "720p" => 1200, //1200 – 1900 
        "1080p" => 1900, // 1900 – 4500
        ];
    private $timeCut = [
        "144p" => 180, //250
        "240p" => 140, //350 
        "360p" => 100, //350 – 800 
        "480p" => 80,//800 – 1200
        "720p" => 40, //1200 – 1900 
        "1080p" => 20, // 1900 – 4500
        ];
    private $resolution = [
        "144p" => ["h"=> 256, "w" => 144],
        "240p" => ["h"=> 426, "w" => 240],
        "360p" => ["h"=> 640, "w" => 360],
        "480p" => ["h"=> 854, "w" => 480],
        "720p" => ["h"=> 1280, "w" => 720],
        "1080p" => ["h"=> 1920, "w" => 1080],
        ];
    
    private $m3u8Header = "#EXTM3U\n";

    private $m3u8content = "";

    private static $publicFolderStorage = "public/" ;

    private static $file_path;

    public function __construct()
    {
        
        $this->ffmpeg = new FFMpeg();
        self::$defaultPath = self::$publicFolderStorage.self::$fileName;
    }

   /* public function __get($property) {
        if (property_exists($this, $property)) {
          return $this->$property;
        }
      }
    
      public function __set($property, $value) {
        if (property_exists($this, $property)) {
          $this->$property = $value;
        }
    
        return $this;
      }
      */

    private function encryptGeneration($savePath){

         $encryptionKey = HLSExporter::generateEncryptionKey();

         Storage::disk()->put($savePath.'secret.key', $encryptionKey);
    }
    public function getFileName($file_path){

        $fileName = basename($file_path).PHP_EOL;
       /* $explodePath = explode($file_path,'/');
        $length = count($explodePath);
        $filename = $explodePath[$length-1];*/

        self::$fileName = $fileName;

        return $fileName;
    }

    public function convertedPath($fileName, $path=null){

        $expl = explode( ".", $fileName);

        if(is_null($path)){

            self::$convertFilePath = $expl[0]."_converted";
            return;

        }

         self::$convertFilePath = $path."/".$expl[0]."_converted";
    }

    public function convertToM3u8($convertType = null ,$file_path=null ){

        $file_path = (is_null($file_path))?self::$defaultPath:$file_path;

        self::$file_path = $file_path;
        // get file name
        $fileName = $this->getFileName($file_path);
        // create the converting path
        $this->convertedPath($fileName);

        // we will use the  switch cas for different convertion

        switch ($convertType) {
            case '144p':
                return $this->reduceTo144p();
                break;

            case '240p':
                return $this->reduceTo240p();
                break;
            
            case '360p':
                return $this->reduceTo360p();
                break;
            case '360p':
                return $this->reduceTo360p();
                break;
            case '480p':
                return $this->reduceTo480p();
                break;
            case '720p':
                return $this->reduceTo720p();
                break;
            case '1080p':
                return $this->reduceTo1080p();
                break;
            case "all":
                $this->reduceTo144p();
                //will be commented or removed
                $this->generateM3u8PlaylistContent(true,"144p", "144p/".self::$convertFilePath."_144p_0_".$this->bitrage['144p'].".m3u8");
                    //_1080p_0_$this->bitrage[720p].m3u8
                $this->reduceTo240p();
                //will be commented or removed
                $this->generateM3u8PlaylistContent(false,"240p", "240p/".self::$convertFilePath."_240p_0_".$this->bitrage['240p'].".m3u8");
                    //_1080p_0_$this->bitrage[720p].m3u8
                $this->reduceTo360p();
                //will be commented or removed
                $this->generateM3u8PlaylistContent(false,"360p", "360p/".self::$convertFilePath."_360p_0_".$this->bitrage["360p"].".m3u8");
                //_1080p_0_$this->bitrage[720p].m3u8
                $this->reduceTo480p();
                //will be commented or removed
                $this->generateM3u8PlaylistContent(false,"480p", "480p/".self::$convertFilePath."_480p_0_".$this->bitrage["480p"].".m3u8");
                //_1080p_0_$this->bitrage[720p].m3u8
                $this->reduceTo720p();
                //will be commented or removed
                $this->generateM3u8PlaylistContent(false,"720p", "720p/".self::$convertFilePath."_720p_0_".$this->bitrage["720p"].".m3u8");
                //_1080p_0_$this->bitrage[720p].m3u8

                //$this->reduceTo1080p();

                //will be commented or removed
                $this->saveM3u8PlaylistFile("testPlaylist.m3u8", $this->m3u8content);

                return $this->successResponse('finished', ['success' => 'file has converted to all format'], 200);
                break;
            
            default:
             return $this->errorResponse('error', 
                ['error' => 'convertion type is not define. 
                    choose between 144p, 240p,360p,480p,720p,1080p'], 404);

        //return $this->errorResponse('undefined.', ['error' => 'not data find'], 404);
                break;
        }

    }

    private function reduceTo1080p(){
        
        $bitrate = (new X264)->setKiloBitrate($this->bitrage['1080p']);      // 1900 – 4500

        $path = '1080p/';

        $path_parts = pathinfo(self::$file_path);

        $savedPath = $path_parts['dirname'].'/'.$path;

        //$this->encryptGeneration($savedPath);

        $this->ffmpeg::open(self::$file_path)
        ->exportForHLS()
        //->withEncryptionKey($encryptionKey,"secret.key")
        ->setSegmentLength($this->timeCut["1080p"]) // optional
        ->addFormat($bitrate, function($media) {
            $media->addFilter("scale=".$this->resolution["1080p"]["h"].":".$this->resolution["1080p"]["w"]);
        })

        ->save(self::$publicFolderStorage.$path.self::$convertFilePath."_1080p.m3u8");

            
    return "operation succefull 1080P";
    }

    private function reduceTo720p(){

        $bitrate = (new X264)->setKiloBitrate($this->bitrage['720p']);       //1200 – 1900 

        $path = '720p/';

        $path_parts = pathinfo(self::$file_path);

        $savedPath = $path_parts['dirname'].'/'.$path;

        //$this->encryptGeneration($savedPath);

        $this->ffmpeg::open(self::$file_path)
        ->exportForHLS()
        //->withEncryptionKey($encryptionKey,"secret.key")
        ->setSegmentLength($this->timeCut["720p"]) // optional
        ->addFormat($bitrate, function($media) {
            $media->addFilter("scale=".$this->resolution["720p"]["h"].":".$this->resolution["720p"]["w"]);
        })

        ->save(self::$publicFolderStorage.$path.self::$convertFilePath."_720p.m3u8");

            
    return "operation succefull 720P";
    }

    private function reduceTo480p(){
        $bitrate = (new X264)->setKiloBitrate($this->bitrage['480p']); //800 – 1200   

         $path = '480p/';

        $path_parts = pathinfo(self::$file_path);

        $savedPath = $path_parts['dirname'].'/'.$path;

        //$this->encryptGeneration($savedPath);

        $this->ffmpeg::open(self::$file_path)
        ->exportForHLS()
        //->withEncryptionKey($encryptionKey,"secret.key")
        ->setSegmentLength($this->timeCut["480p"]) // optional
        ->addFormat($bitrate, function($media) {
            $media->addFilter("scale=".$this->resolution["480p"]["h"].":".$this->resolution["480p"]["w"]);
        })

        ->save(self::$publicFolderStorage.$path.self::$convertFilePath."_480p.m3u8");

    return "operation succefull 480P";
    }

    private function reduceTo360p(){
        $bitrate = (new X264)->setKiloBitrate($this->bitrage['360p']);    //350 – 800    

        $path = '360p/';

        $path_parts = pathinfo(self::$file_path);

        $savedPath = $path_parts['dirname'].'/'.$path;

        //$this->encryptGeneration($savedPath);

        $this->ffmpeg::open(self::$file_path)
        ->exportForHLS()
        //->withEncryptionKey($encryptionKey,"secret.key")
        ->setSegmentLength($this->timeCut["360p"]) // optional
        ->addFormat($bitrate, function($media) {
            $media->addFilter("scale=".$this->resolution["360p"]["h"].":".$this->resolution["360p"]["w"]);
        })

        ->save(self::$publicFolderStorage.$path.self::$convertFilePath."_360p.m3u8");
        return "operation succefull 360P";
    }

    private function reduceTo240p(){
        $bitrate = (new X264)->setKiloBitrate($this->bitrage['240p']);   //350   

         $path = '240p/';

        $path_parts = pathinfo(self::$file_path);

        $savedPath = $path_parts['dirname'].'/'.$path;

        //$this->encryptGeneration($savedPath);

        $this->ffmpeg::open(self::$file_path)
        ->exportForHLS()
        //->withEncryptionKey($encryptionKey,"secret.key")
        ->setSegmentLength($this->timeCut["240p"]) // optional
        ->addFormat($bitrate, function($media) {
            $media->addFilter("scale=".$this->resolution["240p"]["h"].":".$this->resolution["240p"]["w"]);
        })

        ->save(self::$publicFolderStorage.$path.self::$convertFilePath."_240p.m3u8");

            
        return "operation succefull 240P";
    }


    private function reduceTo144p(){
        $bitrate = (new X264)->setKiloBitrate($this->bitrage['144p']);   //250   

         $path = '144p/';

        $path_parts = pathinfo(self::$file_path);

        $savedPath = $path_parts['dirname'].'/'.$path;
        

        //$this->encryptGeneration($savedPath);
      //  var_dump($this->ffmpeg);
        $this->ffmpeg::open(self::$file_path)
        ->exportForHLS()
        //->withEncryptionKey($encryptionKey,"secret.key")
        ->setSegmentLength($this->timeCut["144p"]) // optional
        ->addFormat($bitrate, function($media) {
            $media->addFilter("scale=".$this->resolution["144p"]["h"].":".$this->resolution["144p"]["w"]);
        })

        ->save(self::$publicFolderStorage.$path.self::$convertFilePath."_144p.m3u8");

        //$this->convetTogif ($path.self::$convertFilePath."_144p.gif",256,144);

            
        return "operation succefull 144P";
    }

    public function convertToMkv(){
        
        $this->ffmpeg::open('video.mp4')
        ->export()
        ->inFormat(new \FFMpeg\Format\Video\X264)
        ->addFilter(function (VideoFilters $filters) {
            $filters->resize(new \FFMpeg\Coordinate\Dimension(64, 48));
        })
        ->save('video1.mkv');

        /*$this->ffmpeg::open('video.mp4')
            ->export()
            ->inFormat(new CopyFormat)
            ->save('video.mkv');*/
    }

    public function generateM3u8PlaylistContent($withHeader=true,$listFormat, $listUri=null){
        //_1080p_0_$this->bitrage[720p].m3u8
        $content = "";

        if($withHeader){
            $content .= $this->m3u8Header;
        }
        
        if(!is_array($listFormat)){
            $content .= "#EXT-X-STREAM-INF:BANDWIDTH=".$this->bitrage[$listFormat]."000,RESOLUTION=".$this->resolution[$listFormat]["h"]."x"
                .$this->resolution[$listFormat]["w"]."\n";
            $content .= "$listUri\n";

            $this->m3u8content .= $content;

            return $content;
        }

        foreach ($listFormat as $key => $value) {
            // on écrit dans le document
            $content .= "#EXT-X-STREAM-INF:BANDWIDTH=".$this->bitrage[$listFormat[$key]]."000,RESOLUTION=".$this->resolution[$listFormat[$key]]["h"]."x"
                .$this->resolution[$listFormat[$key]]["w"]."\n";
            $content .= "$listUri[$key]\n";
        }

        $this->m3u8content .= $content;

        return $content;
    }

    public function saveM3u8PlaylistFile($fileNamePath, $content){

        Storage::disk('local')->put(self::$publicFolderStorage.$fileNamePath, $content);

    }

    public function getMediaKey(){
       // die($file_path = public_path());
        $file_path = public_path("secret.key");
        return response()->download($file_path);
        //$file=Storage::disk('public')->get("secret.key");
    
       // return response()->download($file);
    }

    public function convetTogif ($path,$height, $width){
       

         /*   $output=null;
            $retval=null;
            $command = '
            /usr/bin/ffmpeg -i '.self::$file_path.' -vf "fps=10,scale='.$height.':'.$width.':-1:flags=lanczos" 
-c:v pam -f image2pipe - | convert -delay 10 - -loop 0 -layers optimize '.$path;
            exec($command, $output, $retval);
            echo "Returned with status $retval and output:\n";
            print_r($output);
        
        $video = $this->ffmpeg::open(self::$file_path);

       /* $this->ffmpeg::open(self::$file_path)->gif(TimeCode::fromSeconds(10), 
                new Dimension($height, $width), 5)
            ->save('/'.$path);

            $video->update([
                "image_gif" => $video->uid. '.gif',
            ]);*/


    }
}