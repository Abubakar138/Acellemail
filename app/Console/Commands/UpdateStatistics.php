<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;
use Acelle\Model\Customer;

class UpdateStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statistics:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all statistics';

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

        $customers = Customer::all();

        foreach($customers as $customer) {
            if (is_null($customer->getCurrentActiveGeneralSubscription())) {
                continue;
            }

            $campaigns = $customer->campaigns;
            foreach ($campaigns as $campaign) {
                safe_dispatch(new \Acelle\Jobs\UpdateCampaignJob($campaign));
            }

            $lists = $customer->lists;
            foreach ($lists as $list) {
                safe_dispatch(new \Acelle\Jobs\UpdateMailListJob($list));
            }
        }

        return 0;
    }
}
