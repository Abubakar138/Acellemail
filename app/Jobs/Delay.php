<?php

namespace Acelle\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Acelle\Model\Campaign;
use Acelle\Library\Exception\RateLimitExceeded;
use Exception;
use Carbon\Carbon;

class Delay implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    // @important: set the "retry_after" setting in config/queue.php to a value that is greater than $timeout;
    // Otherse, the job shall be released and attempted again, resulting in error like:
    // "[Job] has been attempted too many times or run too long. The job may have previously timed out."

    // @important: https://laravel.com/docs/8.x/queues#failing-on-timeout
    // Sometimes, IO blocking processes such as sockets or outgoing HTTP connections
    // may not ***RESPECT*** your specified timeout. Therefore, when using these features,
    // you should always attempt to specify a timeout using their APIs as well.
    // For example, when using Guzzle, you should always specify a connection and request timeout value.
    public $timeout = 600;
    public $maxExceptions = 1; // This is required if retryUntil is used, otherwise, the default value is 255
    public $failOnTimeout = true;

    // $tries is no longer needed (or effective) due to the retryUntil() method
    // public $tries = 1;
    protected $wait;
    protected $campaign;
    protected $rateTrackers;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($wait, $campaign, $rateTrackers)
    {
        $this->wait = $wait;
        $this->campaign = $campaign;
        $this->rateTrackers = $rateTrackers;
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        // @important: remember that messages might be released over and over
        // if there is any limit setting in place
        // As a result, it is just save to have it retry virtually forever
        return now()->addDays(365);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        try {
            foreach ($this->rateTrackers as $rateTracker) {
                $now = now();
                $rateTracker->test($now);
            }

            // if everything is okie, then just release this job, remove delay flag
            // The JOB_TYPE_DISPATCH_AND_SEND_MESSAGES batch will trigger the THEN callback and continue

            $this->campaign->setDelayFlag(null);
            $this->campaign->logger()->warning('RESUME Campaign');

            return;

        } catch (RateLimitExceeded $ex) {

            // Record last activity. Otherwise, this job shall be erase when running Campaign::checkAndForceRerunCampaigns()
            $this->campaign->debug(function ($info) use ($ex) {
                $info['last_activity_at'] = Carbon::now()->toString();

                // Save delay note
                // @todo: consider making it an interface, rather than access the .delay_note attribute directly like this
                $info['delay_note'] = sprintf($info['last_activity_at'].": delay for %s seconds! %s", $this->wait, $ex->getMessage());

                // Must return;
                return $info;
            });

            $this->campaign->logger()->warning(sprintf("Delay for [ANOTHER] %s seconds! %s", $this->wait, $ex->getMessage()));

            $this->release($this->wait);

        } catch (Throwable $ex) {
            $message = sprintf("Something went wrong with Wait job: %s", $ex->getMessage());
            $this->campaign->logger()->error($message);

            throw new Exception($message);
        } finally {
            //
        }
    }
}
