<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Acelle\Model\Website;
use Acelle\Model\Template;

class WebsiteController extends Controller
{
    public function index(Request $request)
    {
        // authorize
        if (\Gate::denies('list', Website::class)) {
            return $this->notAuthorized();
        }

        return view('websites.index');
    }

    public function create(Request $request)
    {
        // authorize
        if (\Gate::denies('create', Website::class)) {
            return $this->notAuthorized();
        }

        $website = Website::newDefault($request->user()->customer);

        if ($request->isMethod('post')) {
            $validator = $website->createFromArray($request->all());

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('websites.create', [
                    'website' => $website,
                    'errors' => $validator->errors(),
                ], 400);
            }

            return redirect()->action('WebsiteController@show', [
                'uid' => $website->uid,
                'new_site' => true,
            ]);
        }

        return view('websites.create', [
            'website' => $website,
        ]);
    }

    public function list(Request $request)
    {
        // authorize
        if (\Gate::denies('list', Website::class)) {
            return $this->notAuthorized();
        }

        // sort, pagination
        $websites = $request->user()->customer->websites()->search($request->keyword)
            ->orderBy($request->sort_order, $request->sort_direction)
            ->paginate($request->per_page);


        return view('websites.list', [
            'websites' => $websites,
        ]);
    }

    public function delete(Request $request)
    {
        if (isSiteDemo()) {
            return response()->json([
                'status' => 'notice',
                'message' => trans('messages.operation_not_allowed_in_demo'),
            ], 403);
        }

        $websites = Website::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        $total = $websites->count();
        $deleted = 0;
        foreach ($websites->get() as $website) {
            // authorize
            if ($request->user()->customer->can('delete', $website)) {
                $website->delete();
                $deleted += 1;
            }
        }

        return response()->json([
            'message' => trans('messages.websites.deleted', [ 'deleted' => $deleted, 'total' => $total]),
        ]);
    }

    public function show(Request $request)
    {
        $website = Website::findByUid($request->uid);

        // authorize
        if (\Gate::denies('read', $website)) {
            return $this->notAuthorized();
        }

        return view('websites.show', [
            'website' => $website,
        ]);
    }

    public function connectJs(Request $request)
    {
        $website = Website::findByUid($request->uid);

        // Prevent the webapp from being spammed by website JS script
        if (is_null($website)) {
            return response('not avaialble', 404);
        }

        if (!$website->isActive()) {
            // return response('disabled', 403);
            return view('websites.checkJs', [
                'website' => $website,
            ]);
        }

        $content = view('websites.connectJs', [
            'website' => $website,
        ]);

        return response($content)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Content-Type', 'application/javascript');
    }

    public function checkJs(Request $request)
    {
        $website = Website::findByUid($request->uid);
        $website->setConnected();
    }

    public function check(Request $request)
    {
        $website = Website::findByUid($request->uid);

        // authorize
        if (\Gate::denies('read', $website)) {
            return $this->notAuthorized();
        }

        try {
            $website->check();

            return response()->json([
                'message' => trans('messages.website.site_is_connected', [
                    'title' => $website->title,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function connect(Request $request)
    {
        $website = Website::findByUid($request->uid);

        // authorize
        if (\Gate::denies('connect', $website)) {
            return $this->notAuthorized();
        }

        try {
            $website->connect();

            return response()->json([
                'message' => trans('messages.website.site_is_connected_success', [
                    'title' => $website->title,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function disconnect(Request $request)
    {
        $websites = Website::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        $total = $websites->count();
        $done = 0;
        foreach ($websites->get() as $website) {
            // authorize
            if ($request->user()->customer->can('disconnect', $website)) {
                $website->disconnect();
                $done += 1;
            }
        }

        return response()->json([
            'message' => trans('messages.websites.disconnected', [ 'done' => $done, 'total' => $total]),
        ]);
    }
}
