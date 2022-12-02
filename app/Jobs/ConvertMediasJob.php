<?php

namespace App\Jobs;

set_time_limit(3600*2);

use App\Http\Controllers\Api\ConvertController;
use App\Models\Medias;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

class ConvertMediasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(object $media = null)
    {
        $this->media = $media;
        $this->convert = new ConvertController();
    }

    private $media;

    private $convert;

    private $converted_files_datas;

    // private function _updateDB($data){

    //     if($data["success"]){
            
    //         $path = $this->media->current_path;
    
    //         // upload link in anather DB //

    //        // var_dump($data["datas"]);
    
    //         $data = [
    //             "current_path" => env("DAILYMOTION_PLAY_URI").$data["datas"]["id"],
    //             "is_online" => true,
    //         ];
    
    //         Medias::where('id', $this->media->id) /*where("current_path", $path)*/
    //         ->update($data);
    
    //         // purge datas //
            
    //         File::exists($path)?File::delete($path):null;
    
    //         return "upload Ok ";
    
    //     }else{

    //         throw new Exception (isset($data["error"])?$data["error"]["message"]:$data["message"]);
    
    //         //return $data;
    //     }
    // }

    // public function moveToCloud(){

    //     $daily = new DailyMotion() ;

    //     $data = new stdClass();

    //     $data->details = $this->media->details;
    //     $data->path = $this->media->current_path;

    //     //var_dump($this->media);

    //     $data =  $daily->uploadFile($data);

    //     $data = $this->_updateDB($data);

    //     var_dump(($data));
    // }

    private function runConvertion(){
        try {
            $this->converted_files_datas = $this->convert->path($this->media->current_path)->convert()->save();
           // $converted_files_datas =  $this->convert->ouput_files_datas;

            //var_dump($this->convert->formatList);

            $this->saveFormatIntoDB();

            echo "convertion Ok \n";

        } catch (\Throwable $th) {
            throw $th;
        }

        
    }

    private function saveFormatIntoDB(){

        //var_dump($this->converted_files_datas);

        $datas = ["is_converted" => true, "converted_format" => $this->converted_files_datas, 
        'current_path' => $this->converted_files_datas[
            count($this->converted_files_datas)-1 // get last elt (lowest quality)
            ]["current_path"] // get past of lowest quality
        ];
       
        Medias::where('id', $this->media->id) /*where("current_path", $path)*/
        ->update($datas);

        File::exists($this->media->current_path)?File::delete($this->media->current_path):null;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        if(is_null($this->media)){

            $datas = Medias::where('is_converted', 0)->get();

            if($datas){

                foreach ($datas as $this->media) {

                    $this->media = (object) $this->media;

                    if(!is_null($this->media->media_id)){

                        if($this->media->is_online==0){

                            //$this->moveToCloud();
                            $this->runConvertion();

                            UploadOnOpanDriverJob::dispatch();
                            
                        }
                    }
                    
                }
                
            }else {
                var_dump("no datas");
            }
            
        }else{

            //$this-> moveToCloud();
            $this->runConvertion();

            UploadOnOpanDriverJob::dispatch();
            
        }
        
    }
}