<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Acelle\SendingCredit\Model\SendingCreditPlan;

class SendingCreditPlanController extends Controller
{
    public function index()
    {
        return view('sending-credit::sending_credit_plans.index');
    }

    public function select(Request $request)
    {
        $plans = SendingCreditPlan::visible()->orderBy('credits', 'asc')->get();

        return view('sending-credit::sending_credit_plans.select', [
            'plans' => $plans,
        ]);
    }

    public function buy(Request $request)
    {
        $customer = $request->user()->customer;
        $sendingCreditPlan = SendingCreditPlan::findByUid($request->plan_uid);

        $invoice = $customer->createSendingCreditsInvoice(
            $sendingCreditPlan->getPrice(),
            $sendingCreditPlan->currency,
            $sendingCreditPlan->credits,
        );

        // Redirect to checkout process
        return redirect()->action('CheckoutController@billingAddress', [
            'invoice_uid' => $invoice->uid,
        ]);
    }

    public function invoiceList(Request $request)
    {
        $invoices = $request->user()->customer->sendingCreditsInvoices()->select('invoices.*');

        // type filter
        if (isset($request->type)) {
            $invoices = $invoices->where('type', $request->type);
        }

        // status filter
        if (isset($request->status)) {
            if ($request->status == 'pending') {
                $invoices = $invoices->pending();
            } else {
                $invoices = $invoices->notPending()->where('status', $request->status);
            }
        }

        // sort
        if (!empty($request->sort_order)) {
            $invoices = $invoices->orderBy($request->sort_order, $request->sort_direction);
        }

        // pagination
        $invoices = $invoices->paginate($request->per_page);

        // view
        return view('sending-credit::sending_credit_plans.invoiceList', [
            'invoices' => $invoices,
        ]);
    }

    public function transactionList(Request $request)
    {
        $transactions = $request->user()->customer->sendingCreditsTransactions()->select('transactions.*');

        // sort
        if (!empty($request->sort_order)) {
            $transactions = $transactions->orderBy($request->sort_order, $request->sort_direction);
        }

        // status filter
        if (isset($request->status)) {
            $transactions = $transactions->where('transactions.status', $request->status);
        }

        // pagination
        $transactions = $transactions->paginate($request->per_page);

        // view
        return view('sending-credit::sending_credit_plans.transactionList', [
            'transactions' => $transactions,
        ]);
    }
}
