<?php

namespace Acelle\Model;

use Acelle\Model\Invoice;
use Acelle\Model\Customer;

class InvoiceSendingCredits extends Invoice
{
    protected $table = 'invoices';

    public const TYPE_SENDING_CREDITS = 'sending_credits';

    /**
     * Process invoice.
     *
     * @return void
     */
    public function process()
    {
        // @important BIG dependencies and assumption here
        // - Need explicit subscription_id
        $tracker = $this->customer->getCurrentActiveGeneralSubscription()->getSendEmailCreditTracker();

        if ($tracker->isUnlimited()) {
            // Right now, just do nothing
            // throw new \Exception('Cannot add more sending credits, already UNLIMITED');
        } else {
            $tracker->topup($this->sending_credits);
        }
    }

    /**
     * Get billing info.
     *
     * @return void
     */
    public function getBillingInfo()
    {
        $chargeInfo = trans('messages.bill.charge_now');

        return $this->getBillingInfoBase($chargeInfo);
    }

    public function refreshPrice()
    {
        if ($this->isPaid()) {
            throw new \Exception("Can not change paid invoice price!");
        }

        // do nothing
    }

    public function beforeCancel()
    {
    }

    public function checkoutAfterPayFailed($error)
    {
    }
}
