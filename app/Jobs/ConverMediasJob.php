<?php

namespace App\Jobs;

use App\Http\Controllers\Api\ConvertController;
use App\Http\Controllers\Api\OpenDriver\OpenDriveController;
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
    public function __construct($path, $media, $isFilmBande)
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

        // appel de la function de convertion 

          if($convert->convert($this->path /*'users/1/medias/video.mp4'*/, [], array_merge($this->media, ['isFilmBande' =>$this->isFilmBande]))){

              echo "convertion Ok \n cloud link ok";

            }       

    }
}
