<?php

namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\Setting;
use Acelle\Cashier\Cashier;
use Acelle\Model\Invoice;

class InvoicePolicy
{
    use HandlesAuthorization;

    public function delete(User $user, Invoice $invoice, $role)
    {
        switch ($role) {
            case 'admin':
                $can = true;
                break;
            case 'customer':
                $can = $invoice->isNew() && $invoice->customer_id == $user->customer->id;
                break;
        }

        return $can;
    }

    public function download(User $user, Invoice $invoice, $role)
    {
        switch ($role) {
            case 'admin':
                $can = $invoice->isPaid();
                break;
            case 'customer':
                $can = $invoice->isPaid() && $invoice->customer_id == $user->customer->id;
                break;
        }

        return $can;
    }

    public function approve(User $user, Invoice $invoice, $role)
    {
        switch ($role) {
            case 'admin':
                $can = $invoice->isNew() && $invoice->getPendingTransaction();
                break;
            case 'customer':
                $can = false;
                break;
        }

        return $can;
    }

    public function reject(User $user, Invoice $invoice, $role)
    {
        switch ($role) {
            case 'admin':
                $can = $invoice->isNew() && $invoice->getPendingTransaction();
                break;
            case 'customer':
                $can = false;
                break;
        }

        return $can;
    }
}
