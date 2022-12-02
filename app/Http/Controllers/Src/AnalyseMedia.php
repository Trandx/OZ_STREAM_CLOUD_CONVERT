<?php
namespace App\Http\Controllers\Src;

use getID3;

class AnalyseMedia extends getID3 {
    public function __construct($path = null)
    {
         $this->path = $path;
    }
    private $path;
    private $datas;

    public  function path($path){
        $this->path = $path;

        return $this;
    }
    public function details(){

        //$getID3 = new \getID3;
        //$this->datas = $this->analyze($this->path);

        return $this->analyze($this->path);
    }

    public function detailsToObject(){

        return (object) $this->details();
        
        // $data['duration'] =  $this->datas['playtime_string'] ?? null;

        // //var_dump($this->datas);

        // // $data['r_x'] =  $this->datas['video']['resolution_x'];
        // // $data['r_y'] =  $this->datas['video']['resolution_y'];
        // $data['size'] =  $this->datas['filesize'];


    }

}