@extends('layouts.core.login')

@section('title', trans('messages.2fa.email_verification'))

@section('content')
    
    <!-- send reset password email -->
    <form class="" role="form" method="POST" action="{{ action('Email2FAController@emailVerify') }}">
        {{ csrf_field() }}
        
        <div class="panel panel-body p-4 rounded-3 bg-white shadow">                        
            
            @if (session('status'))
                <div class="alert alert-success">
        {{ session('status') }}
                </div>
            @endif
            
            <h4 class="text-semibold mt-0">{{ trans('messages.2fa.email_verification') }}</h4>
            <p>{!! trans('messages.2fa.email_verification.email_sent', [
                'email' => Acelle\Helpers\maskEmail(Auth::user()->email),
            ]) !!}</p>
            
            <div class="form-group has-feedback has-feedback-left{{ $errors->has('code') ? ' has-error' : '' }}">
                <input type="text" class="form-control" name="code"
                    placeholder="{{ trans("messages.2fa.email_verification.enter_code_here") }}"
                    value=""
                />
                <div class="form-control-feedback has-label">
        <i class="icon-envelop5 text-muted"></i>
                </div>
                @if ($errors->has('code'))
                    <span class="help-block">
                        <strong>{{ $errors->first('code') }}</strong>
                    </span>
                @endif                            
            </div>
            
            <button type="submit" class="btn btn-primary">
                {{ trans('messages.2fa.email_verification.verify') }}
            </button>
            <input type="submit" verify-control="resend" type="submit" type="submit" class="btn btn-light" name="resend"
                value="{{ trans('messages.2fa.email.resend_email') }}""
            />

            <p class="mb-0 mt-4">
                <a href="{{ action('UserController@verifySelectMethod') }}" >{{ trans('messages.2fa.select_defferent_method') }}</a>
            </p>
            
            <script>
                $(function() {
                    new CountDownManager({
                        from: {{ Auth::user()->getRemainVerifyCountdown() }},
                        text: '{{ trans('messages.2fa.email.resend_email') }}',
                        button: $('[verify-control="resend"]')
                    });
                });

                var CountDownManager = class {
                    constructor(options) {
                        this.from = options.from;
                        this.text = options.text;
                        this.button = options.button;

                        this.start();
                    }

                    start() {
                        this.check();
                    }

                    check() {
                        if (this.from > 0) {
                            this.button.prop('disabled', true);
                            this.button.val('('+this.from+') ' + this.text);

                            // minus 1
                            this.from -= 1;

                            // coutdown
                            setTimeout(() => {
                                this.check();
                            }, 1000);
                        } else {
                            this.button.prop('disabled', false);
                            this.button.val(this.text);
                        }
                    }
                }
            </script>
        </div>
    </form>
    <!-- /send reset password email -->                
    
@endsection



