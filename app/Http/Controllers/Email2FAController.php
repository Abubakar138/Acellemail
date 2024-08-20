<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Google2FA;

class Email2FAController extends Controller
{
    public function emailVerify(Request $request)
    {
        if (!$request->user()->is2FAEnabled()) {
            $url = session()->get('2fa.redirect', action('HomeController@index'));
            return redirect()->away($url);
        }

        $user = $request->user();

        // save posted data
        if ($request->isMethod('post')) {
            //
            $inputCode = $request->code;

            // resend
            if ($request->resend) {
                $user->sendVerifyCodeEMail();

                return redirect(action('Email2FAController@emailVerify'));
            }

            // make validator
            $validator = \Validator::make($request->all(), [
                'code' => 'required',
            ]);

            // verify code
            $validator->after(function ($validator) use ($user, $inputCode) {
                if (!$user->verifyCode($inputCode)) {
                    $validator->errors()->add('code', trans('messages.2fa.email.code_not_match'));
                }
            });

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('auth.2fa.email.enterCode', [
                    'errors' => $validator->errors(),
                ], 400);
            }

            // success
            $url = session()->get('2fa.redirect', action('HomeController@index'));
            return redirect()->away($url);
        }

        return view('auth.2fa.email.enterCode');
    }
}
