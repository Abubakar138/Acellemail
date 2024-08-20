<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Acelle\Model\Subscription;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        event(new \Acelle\Events\UserUpdated($request->user()->customer));
        $currentTimezone = $request->user()->customer->getTimezone();

        // Last month
        $customer = $request->user()->customer;

        $maxLists = get_tmp_quota($customer, 'list_max');
        $maxCampaigns = get_tmp_quota($customer, 'campaign_max');
        $maxSubscribers = get_tmp_quota($customer, 'subscriber_max');

        $listsCount = $customer->listsCount();
        $listsUsed = ($maxLists == -1) ? 0 : $listsCount / $maxLists;

        $campaignsCount = $customer->campaignsCount();
        $campaignsUsed = ($maxCampaigns == -1) ? 0 : $campaignsCount / $maxCampaigns;

        $subscribersCount = $customer->readCache('SubscriberCount', 0);
        $subscribersUsed = ($maxSubscribers == -1) ? 0 : $subscribersCount / $maxSubscribers;

        if (config('app.cartpaye')) {
            return view('dashboard.cartpaye');
        } else {
            return view('dashboard', [
                'currentTimezone' => $currentTimezone,
                'maxLists' => $maxLists,
                'listsCount' => $listsCount,
                'listsUsed' => $listsUsed,
                'maxCampaigns' => $maxCampaigns,
                'campaignsCount' => $campaignsCount,
                'campaignsUsed' => $campaignsUsed,
                'maxSubscribers' => $maxSubscribers,
                'subscribersCount' => $subscribersCount,
                'subscribersUsed' => $subscribersUsed,
            ]);
        }
    }
}
