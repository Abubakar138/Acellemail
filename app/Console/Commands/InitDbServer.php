<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;

class InitDbServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbserver:init {connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize user database. That is, run migration against the specified connection to set up the database schema';

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
        $connection = $this->argument('connection');
        artisan_migrate($connection);

        return 0;
    }
}
