<?php

namespace Acelle\Http\Middleware;

use Closure;
use PragmaRX\Google2FALaravel\Support\Authenticator;
use Illuminate\Http\Request;

class TwoFA
{
    public function handle($request, Closure $next)
    {
        if (\Acelle\Model\Setting::get('2fa_enable') == 'yes') {
            if ($request->user()->is2FAEnabled()) {
                // save previous url
                session(['2fa.redirect' => url()->full()]);

                // var_dump($this->shouldProvideTwoFactorChallenge($request));die();

                // Select methos to validate
                if ($this->shouldProvideTwoFactorChallenge($request)) {
                    return redirect(action('UserController@verifySelectMethod'));
                }
            } else {
                // not enable mean should set 2fa session = true, in case user want to change 2fa setting while logged in
                $request->user()->set2FAAuthenticated();
            }
        }

        return $next($request);
    }

    protected function shouldProvideTwoFactorChallenge(Request $request): bool
    {
        return $request->user() &&
            !$request->user()->is2FAAuthenticated();
    }
}
