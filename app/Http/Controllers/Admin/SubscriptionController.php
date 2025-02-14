<?php

namespace Acelle\Http\Controllers\Admin;

use Illuminate\Http\Request;

use Acelle\Http\Controllers\Controller;
use Acelle\Model\Subscription;
use Acelle\Model\SubscriptionLog;
use Acelle\Model\PlanGeneral;
use Acelle\Model\Customer;
use Acelle\Library\Facades\SubscriptionFacade;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // authorize
        if (!$request->user()->admin->can('read', new Subscription())) {
            return $this->notAuthorized();
        }

        // If admin can view all subscriptions of their customer
        if (!$request->user()->admin->can('readAll', new Subscription())) {
            $request->merge(array("customer_admin_id" => $request->user()->admin->id));
        }
        $subscriptions = Subscription::all();

        $plan = null;
        if ($request->plan_uid) {
            $plan = PlanGeneral::findByUid($request->plan_uid);
        }

        return view('admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'plan' => $plan,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
        // authorize
        if (!$request->user()->admin->can('read', new Subscription())) {
            return $this->notAuthorized();
        }

        $currentTimezone = $request->user()->admin->getTimezone();

        // If admin can view all subscriptions of their customer
        if (!$request->user()->admin->can('readAll', new Subscription())) {
            $request->merge(array("customer_admin_id" => $request->user()->admin->id));
        }

        $subscriptions = Subscription::general();

        if (isset($request->customer_uid)) {
            $customer = Customer::findByUid($request->customer_uid);
            $subscriptions = $subscriptions->where('customer_id', $customer->id);
        }

        if (isset($request->plan_uid)) {
            $plan = PlanGeneral::findByUid($request->plan_uid);
            $subscriptions = $subscriptions->where('plan_id', $plan->id);
        }

        if (!empty($request->sort_order)) {
            $subscriptions = $subscriptions->orderBy($request->sort_order, $request->sort_direction);
        }

        $subscriptions = $subscriptions->paginate($request->per_page);

        return view('admin.subscriptions._list', [
            'subscriptions' => $subscriptions,
            'currentTimezone' => $currentTimezone
        ]);
    }

    /**
     * Approve pending subscription.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function approve(Request $request, $id)
    {
        $subscription = Subscription::findByUid($request->id);
        $invoice = $subscription->getUnpaidInvoice();

        // authorize
        if (!$request->user()->admin->can('approve', $subscription)) {
            return $this->notAuthorized();
        }

        try {
            // approve last new invoice ~ pay last invoice
            $invoice->approve();

            // log
            SubscriptionFacade::log($subscription, SubscriptionLog::TYPE_ADMIN_APPROVE, $invoice->uid, [
                'amount' => format_price($invoice->total(), $invoice->currency->format),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.subscription.set_active.success'),
        ]);
    }

    /**
     * Cancel subscription at the end of current period.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function disableRecurring(Request $request)
    {
        if (isSiteDemo()) {
            return response()->json(["message" => trans('messages.operation_not_allowed_in_demo')], 404);
        }

        $subscription = Subscription::findByUid($request->id);

        if ($request->user()->admin->can('disableRecurring', $subscription)) {
            $subscription->disableRecurring();
        }

        echo trans('messages.subscription.disabled_recurring');
    }

    /**
     * Cancel subscription at the end of current period.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function enableRecurring(Request $request)
    {
        $subscription = Subscription::findByUid($request->id);

        if ($request->user()->admin->can('enableRecurring', $subscription)) {
            $subscription->enableRecurring();
        }

        echo trans('messages.subscription.enabled_recurring');
    }

    /**
     * Cancel now subscription at the end of current period.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function terminate(Request $request)
    {
        if (isSiteDemo()) {
            return response()->json(["message" => trans('messages.operation_not_allowed_in_demo')], 404);
        }

        $subscription = Subscription::findByUid($request->id);

        if ($request->user()->admin->can('terminate', $subscription)) {
            try {
                $subscription->terminate();
            } catch (\Exception $ex) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $ex->getMessage(),
                ]);
                return;
            }
        }

        echo trans('messages.subscription.terminated');
    }

    /**
     * Subscription invoices.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function invoices(Request $request, $id)
    {
        $subscription = Subscription::findByUid($request->id);

        return view('admin.subscriptions.invoices', [
            'subscription' => $subscription,
        ]);
    }

    /**
     * Reject subscription pending.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function rejectPending(Request $request, $id)
    {
        $subscription = Subscription::findByUid($request->id);
        $invoice = $subscription->getUnpaidInvoice();

        // authorize
        if (!$request->user()->admin->can('rejectPending', $subscription)) {
            return $this->notAuthorized();
        }

        if ($request->isMethod('post')) {
            $validator = \Validator::make($request->all(), ['reason' => 'required']);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('admin.subscriptions.rejectPending', [
                    'subscription' => $subscription,
                    'errors' => $validator->errors(),
                ], 400);
            }

            // try reject
            try {
                // approve last new invoice ~ pay last invoice
                $invoice->reject($request->reason);

                // log
                SubscriptionFacade::log($subscription, SubscriptionLog::TYPE_ADMIN_REJECT, $invoice->uid, [
                    'amount' => format_price($invoice->total(), $invoice->currency->format),
                    'reason' => $request->reason,
                ]);
            } catch (\Exception $ex) {
                $validator->errors()->add('reason', $ex->getMessage());

                return response()->view('admin.subscriptions.rejectPending', [
                    'subscription' => $subscription,
                    'errors' => $validator->errors(),
                ], 400);
            }

            // success
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.subscription.reject_pending.success'),
            ]);
        }

        return view('admin.subscriptions.rejectPending', [
            'subscription' => $subscription,
        ]);
    }

    /**
     * Delete subscription.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        // Get current subscription
        $subscription = Subscription::findByUid($request->id);

        if ($request->user()->admin->can('delete', $subscription)) {
            $subscription->deleteAndCleanup();
        }

        echo trans('messages.subscription.deleted');
    }

    public function replenishSendingCredits(Request $request, $id)
    {
        $subscription = Subscription::findByUid($request->id);

        // authorize
        if (!$request->user()->admin->can('replenishSendingCredits', $subscription)) {
            return $this->notAuthorized();
        }

        if ($request->isMethod('post')) {
            try {
                $subscription->getSendEmailCreditTracker()->setCredits((int)$request->credits);
                return response()->json([
                    'status' => 'success',
                    'message' => trans('messages.subscription.replenish_sending_credits.success'),
                ]);
            } catch (Exception $ex) {
                return response()->json([
                    'status' => 'error',
                    'message' => $ex->getMessage(),
                ], 400);
            }
        }

        return view('admin.subscriptions.replenishSendingCredits', [
            'subscription' => $subscription,
            'remaining' => $subscription->getRemainingEmailCredits(),
        ]);
    }
}
