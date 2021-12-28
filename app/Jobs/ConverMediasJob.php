<?php

namespace App\Jobs;

set_time_limit(3600*2);

use App\Http\Controllers\Api\ConvertController;
use App\Http\Controllers\Api\OpenDriver\OpenDriveController;
use App\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ConverMediasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($path = null, $media = null, $isFilmBande =null)
    {
        $this->path = $path;
        $this->media = $media;
        $this->isFilmBande = $isFilmBande;
        
    }

    private $path;

    private $media;

    private $isFilmBande;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ConvertController $convert)
    {

        if(is_null($this->path)){
            $datas = Media::where(function($q){
                $q->whereNotNull('mediaPath')->where(function($q){
                    $q->whereNull('mediaIsOnCloud')->orWhere('mediaIsOnCloud', 0);
                });
            })->orWhere(function($q){
                $q->whereNotNull('bandePath')->where(function($q){
                    $q->whereNull('bandeIsOnCloud')->orWhere('bandeIsOnCloud', 0);
                });
            })->get();
       
            if($datas){
                
                foreach ($datas as $key => $media) {

                    $media = (object) $media;

                    if(!is_null($media->media_id)){

                        if(!is_null($media->mediaPath) and (is_null($media->mediaIsOnCloud) or $media->mediaIsOnCloud==0)){
                        
                           ConverMediasJob::dispatch($media->mediaPath, ['id' => $media->id], false)->delay(now()->addSeconds(60));

                        }

                        if(!is_null($media->bandePath) and (is_null($media->bandeIsOnCloud) or $media->bandeIsOnCloud==0)){
                        
                           ConverMediasJob::dispatch($media->bandePath, ['id' => $media->id], true)->delay(now()->addSeconds(60));

                        }
                    }

                    

                    if(!is_null($media->saison_id) and (is_null($media->bandeIsOnCloud) or $media->bandeIsOnCloud==0)){

                        ConverMediasJob::dispatch($media->bandePath, ['id' => $media->id], null)->delay(now()->addSeconds(60));

                    }

                    
                }
            }
        }else{

              // appel de la function de convertion 
              
            $convert->convert($this->path /*'users/1/medias/video.mp4'*/, [], array_merge($this->media, ['isFilmBande' =>$this->isFilmBande]));

                echo "convertion Ok \n cloud link ok";

             
        }
    
             

    }
}
