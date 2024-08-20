<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;
use Acelle\Model\Language;
use Acelle\Model\Template;
use Acelle\Model\Setting;
use Acelle\Model\Job;
use Acelle\Model\FailedJob;
use Acelle\Model\JobMonitor;
use Acelle\Model\Plugin;
use Artisan;
use Illuminate\Support\Facades\Session;
use Cache;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Exception;
use Acelle\Library\UpgradeManager;
use Acelle\Model\Notification;
use Acelle\Helpers\LicenseHelper;

class SystemCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'System cleanup';

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
     * @return mixed
     */
    public function handle()
    {
        Artisan::call('route:clear');

        Artisan::call('view:clear');

        // Clean up failed jobs
        Artisan::call('queue:flush');

        Artisan::call('queue:prune-failed');

        return 0;
    }
}
