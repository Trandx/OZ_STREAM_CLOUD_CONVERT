<?php

namespace App\Jobs;

set_time_limit(3600*2);

use App\Http\Controllers\Src\OpenDrive\OpenDrive;
use App\Models\Medias;
//use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

class UploadOnOpanDriverJob implements ShouldQueue
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
        $this->opd = new OpenDrive();
    }

    private $opd, $media;

    private function uploadToDriver(){

        //echo "start";

        echo("star upload \n");

        $folderName = date('Y-m-d').'_'.time();

        echo("star crating folder \n");

        $resp = (array)$this->opd->CreateFolder($folderName)->response();


        //$folder = $resp['datas'];

        if($resp["success"]){

            $folderid = $resp['datas']['FolderID'];

            echo("folder created $folderid \n");

            //$dir = pathinfo($this->media->current_path)['dirname']; 

            $files_path = $this->media->converted_format;

            $error = false;

            $total = count($files_path);

            echo("start upload files in folder $folderid \n");

           foreach ($files_path as $key => $file) {

                $result = (array)$this->opd->UploadFile($file["current_path"],$folderid)->response();

                if($result["success"]){

                    echo "okey ". ($key+1)."/".$total. "\n";
                    //unlink($file["url"]);

                    $files_path[$key]["url"] = $result['datas']['StreamingLink'];
                }else{

                    echo(" error: file ($key+1) does'nt uploaded \n");

                    $error = true;
                    exit();
                }

                //
                    // les procédures de l'upload
                //
            //return $this->jsonGenerateLinkressource();

            }

            // delete folder if error

            if($error){
                /// delete folder
                echo('start deletion \n');

                $this->opd->deleteFolder($resp['datas']['FolderID'], true)->response();

                echo('deletetion finshed \n');

            }else{
                echo(" start saving datas \n");

                // save folder id

                $data = [
                    "folder_id" => $folderid,
                    "files" => $files_path,
                    ]; // bind current folder id and files details

                $this->_updateDB($data);

                echo(" end saving \n");
            }


        }

        
    }

    private function _updateDB($data){

        extract($data); // extract files to $files and folder_id to $forder_id

        foreach ($files as $key => $file) {

            //var_dump($file);
            unlink($file['current_path']);

            unset($data['files'][$key]['current_path']);

        }

        
    
        $data = [
            "current_path" => env("DAILYMOTION_PLAY_URI").$files[count($files)-1]["url"],
            "is_online" => true,
            "converted_format" => $data,
        ];
    
            Medias::where('id', $this->media->id) /*where("current_path", $path)*/
            ->update($data);
    
            // purge datas //
            
            //File::exists($path)?File::delete($path):null;
    
            echo "updating Ok ";
    
       

            //throw new Exception (isset($data["error"])?$data["error"]["message"]:$data["message"]);
    
            //return $data;
        
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(is_null($this->media)){
            $datas = Medias::where('is_online', 0)->get();

            if($datas){

                foreach ($datas as $this->media) {

                    $this->media = (object) $this->media;

                    if(!is_null($this->media->media_id)){

                        if($this->media->is_online==0){

                            //$this->moveToCloud();
                            $this->uploadToDriver();
                            
                        }
                    }
                    
                }
                
            }else {
                echo("no datas");
            }
        }else{

            //$this-> moveToCloud();
            $this->uploadToDriver();
            
        }
    }
}
