<?php

namespace App\Jobs;

use App\Http\Controllers\Api\OpenDriver\OpenDriveController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class OpendDriveUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($path, $folder_id)
    {
        $this->path = $path;
    }

    private $path;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(OpenDriveController $op)
    {
        /*if(File::exists($this->path)){

           // $path = public_path($path);
            $path = File::dirname($this->path);

        }elseif(Storage::exists($this->path)){

           // $path = Storage::path($path);
            $path = File::dirname($this->path);

        }*/

        /// lorsque l'upload est treminé, on ouvre le fichier m3u8 et on écrit le lien à l'intérieur
        echo $this->path;
        
    }
}
