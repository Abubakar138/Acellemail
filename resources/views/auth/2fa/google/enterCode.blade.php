@extends('layouts.core.login')

@section('title', trans('messages.2fa.email_verification'))

@section('content')
    
    <!-- send reset password email -->
    <form class="" role="form" method="POST" action="{{ action('Google2FAController@check') }}">
        {{ csrf_field() }}
        
        <div class="panel panel-body p-4 rounded-3 bg-white shadow">                        
            
            @if (session('status'))
                <div class="alert alert-success">
        {{ session('status') }}
                </div>
            @endif
            
            <h4 class="text-semibold mt-0">{{ trans('messages.2fa.google_authenticator') }}</h4>
            <p>{{ trans('messages.2fa.google_authenticator.please_enter_code') }}</p>
            
            @if ($errors->has('code_invalid'))
                <div class="alert alert-danger">
                    {{ trans('messages.2fa.google_authenticator.code_invalid') }}
                </div>
            @endif

            <div class="mb-3">
                <input name="one_time_password" type="text" class="form-control" required autofocus>
            </div>

            <button type="submit" class="btn btn-primary">
                {{ trans('messages.2fa.google_authenticator.authenticate') }}
            </button>

            <p class="mb-0 mt-4">
                <a href="{{ action('UserController@verifySelectMethod') }}" >{{ trans('messages.2fa.select_defferent_method') }}</a>
            </p>
        </div>
    </form>
    <!-- /send reset password email -->
@endsection