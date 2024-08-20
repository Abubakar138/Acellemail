@extends('layouts.core.login')

@section('title', trans('messages.2fa.email_verification'))

@section('content')
    
    <!-- send reset password email -->
    <form class="" role="form" method="POST" action="{{ action('Google2FAController@saveKey') }}">
        {{ csrf_field() }}

        <input type="hidden" name="key" value="{{ $key }}" />
        <input type="hidden" name="redirect" value="{{ request()->redirect }}" />
        
        <div class="panel panel-body p-4 rounded-3 bg-white shadow">                        
            
            <p>{{ trans('messages.2fa.google_authenticator.scan_code.wording') }}</p>

            <div class="text-center my-3">
                {!! $inlineImageUrl !!}
                <p class="mb-0">
                    {{ $key }}
                </p>
                <p class="mb-0">
                    {{ Auth::user()->email }}
                </p>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary">
                    {{ trans('messages.2fa.google_authenticator.next') }}
                </button>
            </div>
        </div>
    </form>
    <!-- /send reset password email -->
@endsection