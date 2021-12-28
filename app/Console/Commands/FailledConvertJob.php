<?php

namespace App\Console\Commands;

use App\Jobs\ConverMediasJob;
use App\Models\Media;
use Illuminate\Console\Command;

class FailledConvertJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'failledConvertJob:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to run failled convert Job';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        ConverMediasJob::dispatch();

        return Command::SUCCESS;
    }
}
