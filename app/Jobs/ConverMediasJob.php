<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Http\Controllers\FfmpegController as Ffmpeg;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConverMediasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($path, array $format)
    {
        $this->path = $path;

        $this->format = $format;
    }

    private $path;

    private $format;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Ffmpeg $ffmpeg)
    {

        foreach ($this->format as $value) {

            $ffmpeg->convertToM3u8($value, $this->path);

        }

        OpendDriveUploadJob::dispatch()->delay(now()->addSeconds(60));


    }
}
