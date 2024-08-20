<?php

namespace Acelle\Http\Controllers\Admin;

use Acelle\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Acelle\SendingCredit\Model\SendingCreditPlan;

class SendingCreditPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('sending-credit::admin.sending_credit_plans.index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $plans = SendingCreditPlan::search($request->keyword)
            ->orderBy($request->sort_order, $request->sort_direction ? $request->sort_direction : 'asc')
            ->paginate($request->per_page);

        return view('sending-credit::admin.sending_credit_plans._list', [
            'plans' => $plans,
        ]);
    }

    public function create()
    {
        // init
        $plans = SendingCreditPlan::newDefault();

        //
        return view('sending-credit::admin.sending_credit_plans.create', [
            'plan' => $plans,
        ]);
    }

    public function store(Request $request)
    {
        // init
        $plans = SendingCreditPlan::newDefault($request->type);

        // Try to save
        $validator = $plans->saveFromParams($request->all());

        // if error
        if ($validator->fails()) {
            return response()->view('sending-credit::admin.sending_credit_plans.create', [
                'plan' => $plans,
                'errors' => $validator->errors(),
            ], 400);
        }

        // Send messenge
        $request->session()->flash('alert-success', trans('messages.sending_credit_plan.create.success'));

        // redirect
        return redirect()->action('Admin\SendingCreditPlanController@index');
    }

    public function edit(Request $request, $uid)
    {
        // init
        $plans = SendingCreditPlan::findByUid($uid);

        //
        return view('sending-credit::admin.sending_credit_plans.edit', [
            'plan' => $plans,
        ]);
    }

    public function update(Request $request, $uid)
    {
        // init
        $plans = SendingCreditPlan::findByUid($uid);

        // Try to save
        $validator = $plans->saveFromParams($request->all());

        // if error
        if ($validator->fails()) {
            return response()->view('sending-credit::admin.sending_credit_plans.edit', [
                'plan' => $plans,
                'errors' => $validator->errors(),
            ], 400);
        }

        // Send messenge
        $request->session()->flash('alert-success', trans('messages.sending_credit_plan.update.success'));

        // redirect
        return redirect()->action('Admin\SendingCreditPlanController@index');
    }

    public function delete(Request $request, $uid)
    {
        // init
        $plans = SendingCreditPlan::findByUid($uid);

        // delete record
        $plans->delete();

        // retur alert
        return response()->json([
            'status' => 'success',
            'message' => trans('messages.sending_credit_plan.delete.success'),
        ]);
    }

    /**
     * Show item.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function visibilityOn(Request $request, $uid)
    {
        $plan = SendingCreditPlan::findByUid($uid);

        //
        $plan->visibilityOn();

        // Redirect to my lists page
        return response()->json([
            'status' => 'success',
            'message' => trans('messages.plan.showed'),
        ], 201);
    }

    /**
     * Show item.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function visibilityOff(Request $request, $uid)
    {
        $plan = SendingCreditPlan::findByUid($uid);

        //
        $plan->visibilityOff();

        // Redirect to my lists page
        return response()->json([
            'status' => 'success',
            'message' => trans('messages.plan.hidden'),
        ], 201);
    }

    public function deleteConfirm(Request $request)
    {
        $plans = SendingCreditPlan::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        return view('sending-credit::admin.sending_credit_plans.delete_confirm', [
            'plans' => $plans,
        ]);
    }
}
