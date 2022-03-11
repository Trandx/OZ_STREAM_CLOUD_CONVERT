<?php
namespace App\Http\Controllers\NoApi;

use App\Http\Controllers\Api\CustomCloudController;
use App\Http\Controllers\Controller;
use getID3;

class FFMpegController_convert_v2 extends Controller
{
    

    private function defaultRepresentation (){

        $this->r_144p  = [ "bitrage" => 95, "representation" => [256, 144] ];
        $this->r_240p  = [ "bitrage" => 150, "representation" => [426, 240] ];
        $this->r_360p  = [ "bitrage" => 276, "representation" => [640, 360] ];
        $this->r_480p  = [ "bitrage" => 750, "representation" => [ 854, 480] ];
        $this->r_720p  = [ "bitrage" => 2048, "representation" => [1280, 720] ];
        //$this->r_1080p = [ "bitrage" => 4096, "representation" => [ "bitrage" => 1920, 1080] ];
        //$this->r_2k    = [ "bitrage" => 6144, "representation" => [ "bitrage" => 2560, 1440] ];
        //$this->r_4k    = [ "bitrage" => 17408, "representation" => [3840, 2160] ];

        // return [
        //             [ "bitrage" => 95, "representation" => [256, 144] ],
        //             [ "bitrage" => 150, "representation" => [426, 240] ],
        //             [ "bitrage" => 276, "representation" => [640, 360] ],
        //             [ "bitrage" => 750, "representation" => [ 854, 480] ],
        //             [ "bitrage" => 2048, "representation" => [1280, 720] ],
        //             //[ "bitrage" => 4096, "representation" => [ "bitrage" => 1920, 1080] ],
        //             //[ "bitrage" => 6144, "representation" => [ "bitrage" => 2560, 1440] ],
        //             //[ "bitrage" => 17408, "representation" => [3840, 2160] ],
        //];

    }

    public function __construct(){

        $this->defaultRepresentation();

    }

    private $r_144p ;
    private $r_240p ;
    private $r_360p ;
    private $r_480p ;
    private $r_720p ;
    private $r_1080p;
    private $r_2k   ;
    private $r_4k   ;

    public $file_path = null;
    
    private $comand = "ffmpeg -i ";

    private $file_to_upload = [];
    private $formatList = [];

    public function save($output_file=null){

        if($output_file){
            $this->output_file = pathinfo($output_file);
            // create directory //
                
        }else{
            $this->output_file = pathinfo($this->file_path);
        }

        if(!is_dir($this->output_file['dirname'])){
             mkdir($this->output_file['dirname']);
         }

        return $this->convertTo();
        
    }   

    public function open($link){

        $this->file_path = $link;
        $this->comand .= $this->file_path;
        return $this;
        
    }
    public function format(array $formats = null){
        if($formats){

            foreach ($formats as $value) {
                $value = 'r_'.$value;
                $this->formatList[]= $this->{$value};
            }

        }else{

            $this->formatList = [$this->r_144p, $this->r_240p, $this->r_360p, $this->r_480p,
                      $this->r_720p, /*$this->r_1080p, $this->r_2k, $this->r_4k*/];

        }
        return $this;
    }

    public function convertTo(){

            if(count($this->formatList) != 0){
                foreach ($this->formatList as $key => $value) {
                    if($key != 0){
                        $this->comand .=" -acodec copy";
                    }else{
                        $this->comand .="  -c:v libx264";
                    }

                    $dir = $this->output_file['dirname'].'/'??'';
                    
                    $url = $dir.$this->output_file['filename']
                    .'_'.$value["representation"][1].'.'.$this->output_file['extension'];
                     
                    // file_exists($url)?unlink($url) :false; //delete file if existe
                    
                    $this->comand .= " -vf  scale=".$value['representation'][0].":".$value['representation'][1]." -b:v ".$value['bitrage'].'k '.$url.' -y';
                    
                    $this->file_to_upload[$key] = [ "url" => $url,
                    "resolution" => $value["representation"][1].'p',
                    ];
            
                }
                
                exec($this->comand);
                
                $this->analyseVideoToUpload();
                    
                return $this;
            }else{
                return " representaion is required";
            }

    }

    public function jsonGenerateLinkressource(){

    }

    public function convert($path, array $formatList = null, $options){

        return $this->open($path)->format($formatList)->save();//->uploadToDriver($options);

    }

    public function uploadToDriver($options){
        $custom = new CustomCloudController();

        $custom->uploadDirectory($this->file_to_upload, $options);
            //
                // les procédures de l'upload
            //
        return $this->jsonGenerateLinkressource();

    }

    public function analyseVideoToUpload(){
       
        $getID3 = new getID3;
        
        //var_dump($this->file_to_upload);
        
            foreach ($this->file_to_upload as $key =>$value) {
                
                $this->dataAnalyseVideo = $getID3->analyze($value['url']);

                if(isset($this->dataAnalyseVideo['playtime_string'])){
                
                $data['duration'] =  $this->dataAnalyseVideo['playtime_string'];
        
                }

                $data['r_x'] =  $this->dataAnalyseVideo['video']['resolution_x'];
                $data['r_y'] =  $this->dataAnalyseVideo['video']['resolution_y'];
                $data['size'] =  $this->dataAnalyseVideo['filesize'];

                $this->file_to_upload[$key]["details"] = $data;
            }
            
            return $this;

      
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