<?php

namespace Acelle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Acelle\Library\Traits\Trackable;
use Acelle\Library\Contracts\CampaignInterface;
use Carbon\Carbon;
use DB;

class RunCampaign implements ShouldQueue
{
    use Trackable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected CampaignInterface $campaign;

    public $timeout = 300;
    public $failOnTimeout = true;
    public $tries = 1;
    public $maxExceptions = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(CampaignInterface $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->campaign->isPaused()) {
            return;
        }

        try {
            $sessionId = date('Y-m-d_H:i:s');
            $startAt = Carbon::now();
            $this->campaign->cleanupDebug();
            $this->campaign->debug(function ($info) use ($startAt) {
                $info['start_at'] = $startAt->toString();
                return $info;
            });

            $this->campaign->logger()->info("Launch campaign from job: session {$sessionId}");

            $this->campaign->logger()->warning('Pre: set up before send');
            $this->campaign->prepare();
            $this->campaign->logger()->warning('After: set up before send');
            $this->campaign->run();

            // after executing campaign.run(), this job just finishes
        } catch (\Throwable $e) {
            $errorMsg = "Error scheduling campaign: ".$e->getMessage()."\n".$e->getTraceAsString();

            // In case the error message size is too large
            $errorMsg = substr($errorMsg ?? '', 0, 1000);

            $this->campaign->setError($errorMsg);

            // To set the job to failed
            throw $e;
        }
    }
}
