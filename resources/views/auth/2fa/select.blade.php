@extends('layouts.core.login')

@section('title', trans('messages.2fa.email_verification'))

@section('content')
    
    <!-- send reset password email -->
    <form class="" role="form" method="POST" action="{{ action('UserController@verifySelectMethod') }}">
        {{ csrf_field() }}
        
        <div class="panel panel-body p-4 rounded-3 bg-white shadow">                        
            
            @if (session('status'))
                <div class="alert alert-success">
        {{ session('status') }}
                </div>
            @endif
            
            <h4 class="text-semibold mt-0">{{ trans('messages.2fa.select_metthod') }}</h4>
            <p class="mb-2">{{ trans('messages.2fa.select.wording') }}</p>

            @if (Auth::user()->is2FAEmailEnabled())
                <button type="submit" class="btn btn-default mb-1 mt-2">
                    <span class="material-symbols-rounded me-1">
                        mail
                    </span>
                    {{ trans('messages.2fa.email.send_email') }}
                </button>
            @endif

            @if (Auth::user()->is2FAGoogleAuthentictorEnabled())
                <a class="btn btn-default mt-2" href="{{ action('Google2FAController@enterCode') }}">
                    <span class="material-symbols-rounded me-1">
                        phonelink_lock
                    </span>
                    {{ trans('messages.2fa.use_google_authenticator_app') }}
                </a>
            @endif
        </div>
    </form>
    <!-- /send reset password email -->                
    
@endsection