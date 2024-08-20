<?php

namespace Acelle\Http\Controllers\Auth;

use Acelle\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Acelle\Model\Setting;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateLogin(\Illuminate\Http\Request $request)
    {
        $rules = [
            $this->username() => 'required',
            'password' => 'required'
        ];

        if (Setting::isYes('login_recaptcha') && !Setting::isYes('theme.beta')) {
            // @hCaptcha
            if (\Acelle\Model\Setting::getCaptchaProvider() == 'hcaptcha') {
                $hcaptcha = \Acelle\Hcaptcha\Client::initialize();

                if (!$hcaptcha->check($request)) {
                    $rules['captcha_invalid'] = 'required';
                }
            } else {
                if (!\Acelle\Library\Tool::checkReCaptcha($request)) {
                    $rules['recaptcha_invalid'] = 'required';
                }
            }
        }

        $this->validate($request, $rules);
    }

    public function authenticated($request, $user)
    {
        // If user is not activated
        if (!$user->activated) {
            $uid = $user->uid;
            auth()->logout();
            return view('notActivated', ['uid' => $uid]);
        }

        return redirect()->intended('/');
    }

    // /**
    //  * Log the user out of the application.
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    //  */
    // public function logout(Request $request)
    // {
    //     // keep sessions when logout
    //     $keepSessions = [];
    //     if ($request->session()->has('two_factor_authenticated')) {
    //         $keepSessions['two_factor_authenticated'] = true;
    //     }

    //     // Original logout code
    //     $this->guard()->logout();

    //     $request->session()->invalidate();

    //     $request->session()->regenerateToken();

    //     if ($response = $this->loggedOut($request)) {
    //         return $response;
    //     }

    //     // keep sessions when logout
    //     foreach ($keepSessions as $key => $value) {
    //         $request->session()->put($key, $value);
    //     }

    //     return $request->wantsJson()
    //         ? new JsonResponse([], 204)
    //         : redirect('/');
    // }
}
