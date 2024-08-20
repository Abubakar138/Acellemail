<?php

namespace Acelle\Jobs;

use Acelle\Model\MailList;
use Acelle\Model\Blacklist;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class UpdateMailListJob extends Base implements ShouldBeUnique
{
    public $list;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(MailList $list)
    {
        $this->list = $list;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (config('app.saas') && is_null($this->list->customer->getCurrentActiveGeneralSubscription())) {
            return;
        }

        $this->list->updateCachedInfo();
        // blacklist new emails (if any)
        Blacklist::doBlacklist($this->list->customer);
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return $this->list->id;
    }
}
