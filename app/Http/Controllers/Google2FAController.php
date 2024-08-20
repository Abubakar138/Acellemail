<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Google2FA;

class Google2FAController extends Controller
{
    public function check(Request $request)
    {
        $rules = [];
        // Verify the code
        $isValid = Google2FA::verifyKey($request->user()->google_2fa_secret_key, $request->one_time_password);

        if (!$isValid) {
            $rules['code_invalid'] = 'required';
        }

        // Validator
        $validator = \Validator::make($request->all(), $rules);

        // redirect if fails
        if ($validator->fails()) {
            return response()->view('auth.2fa.google.enterCode', [
                'errors' => $validator->errors(),
            ], 400);
        }

        //
        $request->user()->set2FAAuthenticated();

        // success
        $url = session()->get('2fa.redirect', action('HomeController@index'));
        return redirect()->away($url);
    }

    public function enterCode(Request $request)
    {
        if (!$request->user()->is2FAEnabled()) {
            $url = session()->get('2fa.redirect', action('HomeController@index'));
            return redirect()->away($url);
        }

        return view('auth.2fa.google.enterCode');
    }

    public function generateSecretKey(Request $request)
    {
        $key = Google2FA::generateSecretKey();
        $inlineImageUrl = Google2FA::getQRCodeInline(
            \Acelle\Model\Setting::get('site_name'),
            $request->user()->email,
            $key
        );
        return view('auth.2fa.google.generateSecretKey', [
            'key' => $key,
            'inlineImageUrl' => $inlineImageUrl,
        ]);
    }

    public function saveKey(Request $request)
    {
        $user = $request->user();
        $user->google_2fa_secret_key = $request->key;
        $user->save();

        return redirect()->away($request->redirect)
            ->with('alert-success', trans('messages.2fa.google_authenticator.save_success'));
    }
}
