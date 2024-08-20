@extends('layouts.core.frontend_no_subscription', [
    'menu' => 'profile',
])

@section('title', trans('messages.my_profile'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item active">{{ trans('messages.profile') }}</li>
        </ul>
        <h1>
            <span class="material-symbols-rounded">security</span> {{ trans('messages.2fa.two_factor_authentication') }}
        </h1>
    </div>

@endsection

@section('content')

    @include("account._menu", [
        'menu' => '2fa',
    ])

    <form id="TwoFAForm" enctype="multipart/form-data" action="{{ action('Admin\TwoFAController@save') }}" method="POST" class="form-validaate-jquery">
        {{ csrf_field() }}

        

        <div class="row">
            <div class="col-md-8">
                <p>{{ trans('messages.2fa.intro') }}</p>

                <div class="form-group">
                    <div class="d-flex">
                        <div class="me-3">
                            <label class="checker">
                                <input type="hidden" name="enable_2fa" value="0" class="styled4">
                                <input data-control="2fa-checker" {{ Auth::user()->enable_2fa ? 'checked' : '' }} type="checkbox" name="enable_2fa" value="1" class="styled4">
                                <span class="checker-symbol"></span>
                            </label>
                        </div>
                        <div>
                            <div class="fw-600">{{ trans('messages.2fa.enable_2fa') }}</div>
                            <p class="mb-0">{{ trans('messages.2fa.enable_2fa.intro') }}</p>
                        </div>
                    </div>
                </div>

                <div data-control="2fa-methods" class="" style="display:none">
                    <table class="table table-box pml-table mt-2" current-page="1">
                        <tbody><tr>
                            <td class="pe-2" width="1%">
                                <span class="material-symbols-rounded fs-2">
                                    mark_email_read
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center mb-1">
                                    <h4 class="mb-0 me-3">{{ trans('messages.2fa.method.email') }}</h4>
                                </div>
                                <p class="mb-0">{!! trans('messages.2fa.method.email.wording', [
                                    'email' => Auth::user()->email,
                                ]) !!}</p>
                            </td>
                            <td class="text-end">
                                <label class="checker">
                                    <input type="hidden" name="enable_2fa_email" value="0" class="styled4">
                                    <input {{ Auth::user()->enable_2fa_email ? 'checked' : '' }} type="checkbox" name="enable_2fa_email" value="1" class="styled4">
                                    <span class="checker-symbol"></span>
                                </label>
                            </td>
                        </tr>
                        <tr class="border-bottom-0">
                            <td class="pe-2" width="1%">
                                <span class="material-symbols-rounded fs-2">
                                    phonelink_lock
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center mb-1">
                                    <h4 class="mb-0 me-3">{{ trans('messages.2fa.method.google_authenticator') }}</h4>
                                </div>
                                <p class="mb-0">{{ trans('messages.2fa.method.google_authenticator.wording') }}</p>
                                
                                <div data-control="2fa-ga-settings" class="mt-3" style="display:none">
                                    <hr>
                                    @if (Auth::user()->google_2fa_secret_key)         
                                        <p>{{ trans('messages.2fa.google_authenticator.key_exist') }}</p>
                                        <div class="my-2 text-center">
                                            {!! Auth::user()->getInlineGAImage() !!}
                                            <p class="mb-0">
                                                {{ Auth::user()->google_2fa_secret_key }}
                                            </p>
                                            <p class="mb-0">
                                                {{ Auth::user()->email }}
                                            </p>
                                        </div>
                                        <hr>
                                        <div class="mt-2">
                                            <a href="{{ action('Google2FAController@generateSecretKey', [
                                                'redirect' => action('Admin\TwoFAController@index'),
                                            ]) }}" class="btn btn-primary">{{ trans('messages.2fa.google_authenticator.re-create') }}</a>
                                        </div>
                                    @else
                                        <p class="alert alert-warning">{{ trans('messages.2fa.google_authenticator.key_not_exist') }}</p>

                                        <a href="{{ action('Google2FAController@generateSecretKey', [
                                            'redirect' => action('Admin\TwoFAController@index'),
                                        ]) }}" class="btn btn-primary">{{ trans('messages.2fa.google_authenticator.setup') }}</a>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end">
                                <label class="checker">
                                    <input type="hidden" name="enable_2fa_google_authenticator" value="0" class="styled4">
                                    <input data-control="2fa-ga-checker" {{ Auth::user()->enable_2fa_google_authenticator ? 'checked' : '' }} type="checkbox" name="enable_2fa_google_authenticator" value="1" class="styled4">
                                    <span class="checker-symbol"></span>
                                </label>
                            </td>
                        </tr>
                    </tbody></table>
                </div>
                
                {{-- <div class="text-left mt-4">
                    <button class="btn btn-secondary"><i class="icon-check"></i> {{ trans('messages.save') }}</button>
                </div> --}}
            </div>
        </div>
    <form>

    <script>
        $(function() {
            new TwoFaSetting({
                enableChecker: $('[data-control="2fa-checker"]'),
                methodsBox: $('[data-control="2fa-methods"]'),
                gaChecker: $('[data-control="2fa-ga-checker"]'),
                gaSettingsBox: $('[data-control="2fa-ga-settings"]'),
            });

            new SettingsManager({
                form: $('#TwoFAForm'),
            });
        });

        var SettingsManager = class {
            constructor(options) {
                this.form = options.form;

                // 
                this.events();
            }

            save() {
                $.ajax({
                    method: "POST",
                    url: this.form.attr('action'),
                    data: this.form.serialize(),
                })
                .done(function( res ) {
                    notify('success', res.status, res.message);
                });
            }

            events() {
                var _this = this;

                this.form.on('submit', function(e) {
                    e.preventDefault();

                    _this.save();
                });

                this.form.find(':input').on('change', function() {
                    _this.save();
                });
            }
        }

        var TwoFaSetting = class {
            constructor(options) {
                this.enableChecker = options.enableChecker;
                this.methodsBox = options.methodsBox;
                this.gaChecker = options.gaChecker;
                this.gaSettingsBox = options.gaSettingsBox;

                // toogle methods
                this.toggleMethods();

                // toogle ga settings box
                this.toggleGaSettingsBox();

                // events
                this.events();
            }

            is2FAEnabled() {
                return this.enableChecker.is(':checked');
            }

            isGaEnabled() {
                return this.gaChecker.is(':checked');
            }

            toggleMethods() {
                if (this.is2FAEnabled()) {
                    this.methodsBox.show();
                } else {
                    this.methodsBox.hide();
                }
            }

            toggleGaSettingsBox() {
                if (this.isGaEnabled()) {
                    this.gaSettingsBox.show();
                } else {
                    this.gaSettingsBox.hide();
                }
            }

            events() {
                var _this = this;

                this.enableChecker.on('change', function() {
                    _this.toggleMethods();
                });

                this.gaChecker.on('change', function() {
                    _this.toggleGaSettingsBox();
                });
            }
        }
    </script>

@endsection
