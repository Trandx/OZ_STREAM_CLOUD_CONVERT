<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class UpdateLinkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($finalPath, $option)
    {
        $this->finalPath = $finalPath;
        $this->option = $option;
    }

    private $finalPath, $option;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Media $media)
    {

        $media = $media->find( $this->option['id']);

        $isFilmBande = $this->option['isFilmBande'];

        $finalPath = 'users'.explode('users', $this->finalPath)[1];

       // var_dump ($this->option['id']);

        if(!is_null($media->media_id)){

             if(!$isFilmBande){

                Storage::delete($media->mediaPath);

                 $media->mediaPath = $finalPath;

                // $media->finalMediaLink = $finalPath;

                $media->mediaIsOnCloud = true;

             }else{

                Storage::delete( $media->bandePath);

                 $media->bandePath = $finalPath;

                 //$media->finalBandeLink = $finalPath;

                 $media->bandeIsOnCloud = true;

             }
             
         }

         if(!is_null($media->saison_id)){

             $media->bandePath = $finalPath;

             //$media->finalBandeLink = $finalPath;

            $media->bandeIsOnCloud = true;
             
         }

         $media->save();
         
    }
}
