<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSendingCredits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $customers = \Acelle\Model\Customer::all();

        foreach($customers as $customer) {
            $this->updateCredits($customer);
        }
    }

    public function updateCredits($customer)
    {
        $subscription = $customer->getCurrentActiveSubscription();

        if (!$subscription) {
            return;
        }

        if (config('custom.distributed_worker')) {
            $key = 'subscription-send-email-rate-tracking-log-'.$subscription->uid;
            $tracker = new \Acelle\Library\InMemoryRateTracker($key);
        } else {
            // Get the limits from plan
            $file = storage_path('app/quota/subscription-send-email-rate-tracking-log-'.$subscription->uid);
            $tracker = new \Acelle\Library\RateTracker($file);
        }

        $timeAmount = $subscription->planGeneral->frequency_amount;
        $timeUnit = $subscription->planGeneral->frequency_unit;
        $countFrom = now()->subtract("{$timeAmount} {$timeUnit}");
        $used = (int)$tracker->getCreditsUsed($countFrom);

        $creditTracker = $subscription->getSendEmailCreditTracker();
        $credits = (int)$subscription->planGeneral->getOption('email_max');
        $remaining = $credits - $used;

        // echo "*** {$timeAmount} {$timeUnit}: {$used} / {$remaining} / {$credits}";

        if ($credits == -1) {
            $creditTracker->setCredits($credits); // unlimited credit, just fine
        } elseif ($remaining > 0) {
            $creditTracker->setCredits($remaining);
        } else {
            $creditTracker->setCredits(0);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
