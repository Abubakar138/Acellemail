<?php

namespace Acelle\Http\Controllers\Admin;

use Acelle\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Google2FA;

class TwoFAController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.twofa.index');
    }

    public function save(Request $request)
    {
        $user = $request->user();

        $user->enable_2fa = $request->enable_2fa;
        $user->enable_2fa_email = $request->enable_2fa_email;
        $user->enable_2fa_google_authenticator = $request->enable_2fa_google_authenticator;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.2fa.saved'),
        ]);
    }
}
