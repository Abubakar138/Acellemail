<?php

namespace Acelle\Http\Controllers\Api\Public;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Acelle\Events\MailListSubscription;
use Acelle\Model\Subscriber;
use Acelle\Model\MailList;
use Acelle\Model\IpLocation;

/**
 * /api/v1/lists/{list_id}/subscribers - API controller for managing list's subscribers.
 */
class SubscriberController extends Controller
{
    /**
     * Create subscriber for a mail list.
     *
     * POST /api/v1/lists/{list_id}/subscribers
     *
     * @param \Illuminate\Http\Request $request All subscriber information: EMAIL (required), FIRST_NAME (?), LAST_NAME (?),... (depending on the list fields configuration)
     * @param string                   $list_id List's id
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $list = MailList::findByUid($request->list_uid);

            // authorize
            if (!$list) {
                return \Response::json(array('message' => trans('List not found')), 400);
            }

            // if (!$user->can('update', [ $list, $more = 1 ])) {
            //     return response()->json(['message' => 'Unauthorized!'], 401);
            // }

            // if (!$user->can('addMoreSubscribers', [ $list, $more = 1 ])) {
            //     return response()->json(['message' => 'List quota exceeded'], 403);
            // }

            // status not allowed
            if ($request->status) {
                return response()->json(['message' => 'Status is not allowed'], 403);
            }

            // status is invalid
            if ($request->has('status')) {
                if (!in_array($request->status, [
                    Subscriber::STATUS_SUBSCRIBED,
                    Subscriber::STATUS_UNSUBSCRIBED,
                    Subscriber::STATUS_UNCONFIRMED,
                ])) {
                    return response()->json(['message' => 'Subscriber status is not valid, allowed values are: subscribed, unsubscribed, unconfirmed'], 403);
                }
            }

            // Validate & and create subscriber
            // Throw ValidationError exception in case of failure
            list($validator, $subscriber) = $list->subscribe($request, MailList::SOURCE_API);

            if (is_null($subscriber)) {
                return response()->json($validator->messages(), 403);
            }

            // update tags
            if ($request->tag) {
                $subscriber->updateTags(explode(',', $request->tag));
            }

            // update status \ overides default one
            if ($request->has('status')) {
                $subscriber->status = $request->status;
                $subscriber->save();
            }

            return \Response::json(array(
                'status' => 1,
                'message' => ($list->subscribe_confirmation) ? trans('messages.subscriber.confirmation_email_sent') : trans('messages.subscriber.created'),
                'subscriber_uid' => $subscriber->uid,
            ), 200);
        } catch (\Exception $ex) {
            return \Response::json(array('message' => $ex->getMessage()), 500);
        }
    }
}
