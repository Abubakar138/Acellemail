<?php

namespace Acelle\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;
use Acelle\Model\Campaign;
use Acelle\Model\Customer;

class MonitorCampaignController extends Controller
{
    // public function index(Request $request)
    // {
    //     $campaigns = Campaign::error()->orderBy('updated_at', 'DESC')->get();
    //     $html = [];
    //     foreach($campaigns as $campaign) {
    //         $html[] = "<p title='{$campaign->last_error}'>{$campaign->uid} ({$campaign->customer->user->email}) <p>";
    //     }

    //     return response(empty($html) ? 'no campaign' : implode('', $html ));
    // }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $campaigns = Campaign::query();

        return view('admin.monitor_campaigns.index', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $campaigns = Campaign::query()
            ->search($request->keyword)
            ->filter($request);

        if ($request->status) {
            $campaigns = $campaigns->byStatus($request->status);
        }

        if ($request->customer_uid) {
            $campaigns = $campaigns->byCustomer(Customer::findByUid($request->customer_uid));
        }

        $campaigns = $campaigns->orderBy($request->sort_order, $request->sort_direction)
            ->paginate($request->per_page);

        return view('admin.monitor_campaigns.list', [
            'campaigns' => $campaigns,
        ]);
    }
}
