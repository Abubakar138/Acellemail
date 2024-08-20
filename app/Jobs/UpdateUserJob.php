<?php

namespace Acelle\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;

class UpdateUserJob extends Base implements ShouldBeUnique
{
    protected $customer;

    public $timeout = 120;

    public function __construct($customer)
    {
        $this->customer = $customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (config('app.saas') && !is_null($this->customer->getCurrentActiveGeneralSubscription())) {
            $this->customer->updateCache();
        }
    }

    public function uniqueId()
    {
        return $this->customer->id;
    }
}
