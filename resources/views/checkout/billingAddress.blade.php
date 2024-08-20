@extends('layouts.core.frontend', [
	'menu' => '',
])

@section('title', trans('messages.balance.your_balance'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item active">{{ trans('messages.invoice.checkout') }}</li>
        </ul>
        <h1>
            <span class="text-semibold">
                <span class="material-symbols-rounded">payments</span>
                {{ trans('messages.address') }}
            </span>
        </h1>
    </div>

@endsection

@section('content')
    @include('checkout._steps', [
        'step' => 'address',
    ])

    <div class="row">
        <div class="col-md-10">
            <form class="billing-address-form" action="{{ action('CheckoutController@billingAddress', [
                'invoice_uid' => $invoice->uid,
            ]) }}"
                method="POST"
            >
                {{ csrf_field() }}
                <div class="mt-5">
                    <div class="border p-4 rounded shadow-sm  bg-white bg-white">
                        <div class="">
                            <div class="d-flex align-items-center mb-3">
                                <p class="me-3 mb-0">
                                    <span class="topup-header-icon">
                                        <span class="material-symbols-rounded">
                                            contact_mail
                                        </span>
                                    </span>
                                </p>
                                <span class="display-3">
                                    {{ trans('messages.billing_address') }}
                                </span>
                            </div>
                            <hr>
                            <div>
                                <div class="row">
                                    @if (get_localization_config('show_last_name_first', Auth()->user()->customer->getLanguageCode()))
                                        <div class="col-md-6">
                                            <div class="form-group {{ $errors->has('billing_last_name') ? 'has-error' : '' }}">
                                                <label>
                                                    {{ trans('messages.last_name') }}
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div>
                                                    <input value="{{ $invoice->billing_last_name }}"
                                                        type="text"
                                                        name="billing_last_name"
                                                        class="form-control required {{ $errors->has('billing_last_name') ? 'is-invalid' : '' }}"
                                                    />
                                                </div>
                                                @if ($errors->has('billing_last_name'))
                                                    <div class="help-block">
                                                        {{ $errors->first('billing_last_name') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group {{ $errors->has('billing_first_name') ? 'has-error' : '' }}">
                                                <label>
                                                    {{ trans('messages.first_name') }}
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div>
                                                    <input value="{{ $invoice->billing_first_name }}"
                                                        type="text"
                                                        name="billing_first_name"
                                                        class="form-control required {{ $errors->has('billing_first_name') ? 'is-invalid' : '' }}"
                                                    />
                                                </div>
                                                @if ($errors->has('billing_first_name'))
                                                    <div class="help-block">
                                                        {{ $errors->first('billing_first_name') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else 
                                        <div class="col-md-6">
                                            <div class="form-group {{ $errors->has('billing_first_name') ? 'has-error' : '' }}">
                                                <label>
                                                    {{ trans('messages.first_name') }}
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div>
                                                    <input value="{{ $invoice->billing_first_name }}"
                                                        type="text"
                                                        name="billing_first_name"
                                                        class="form-control required {{ $errors->has('billing_first_name') ? 'is-invalid' : '' }}"
                                                    />
                                                </div>
                                                @if ($errors->has('billing_first_name'))
                                                    <div class="help-block">
                                                        {{ $errors->first('billing_first_name') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group {{ $errors->has('billing_last_name') ? 'has-error' : '' }}">
                                                <label>
                                                    {{ trans('messages.last_name') }}
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div>
                                                    <input value="{{ $invoice->billing_last_name }}"
                                                        type="text"
                                                        name="billing_last_name"
                                                        class="form-control required {{ $errors->has('billing_last_name') ? 'is-invalid' : '' }}"
                                                    />
                                                </div>
                                                @if ($errors->has('billing_last_name'))
                                                    <div class="help-block">
                                                        {{ $errors->first('billing_last_name') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                        
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group {{ $errors->has('billing_email') ? 'has-error' : '' }}">
                                            <label>
                                                {{ trans('messages.email_address') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div>
                                                <input value="{{ $invoice->billing_email }}"
                                                    type="email"
                                                    name="billing_email"
                                                    class="form-control required {{ $errors->has('billing_email') ? 'is-invalid' : '' }}"
                                                />
                                            </div>
                                            @if ($errors->has('billing_email'))
                                                <div class="help-block">
                                                    {{ $errors->first('billing_email') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group {{ $errors->has('billing_phone') ? 'has-error' : '' }}">
                                            <label>
                                                {{ trans('messages.phone') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div>
                                                <input value="{{ $invoice->billing_phone }}"
                                                    type="text"
                                                    name="billing_phone"
                                                    class="form-control required {{ $errors->has('billing_phone') ? 'is-invalid' : '' }}"
                                                />
                                            </div>
                                            @if ($errors->has('billing_phone'))
                                                <div class="help-block">
                                                    {{ $errors->first('billing_phone') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group {{ $errors->has('billing_address') ? 'has-error' : '' }}">
                                            <label>
                                                {{ trans('messages.address') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div>
                                                <input value="{{ $invoice->billing_address }}"
                                                    type="text"
                                                    name="billing_address"
                                                    class="form-control required {{ $errors->has('billing_address') ? 'is-invalid' : '' }}"
                                                />
                                            </div>
                                            @if ($errors->has('billing_address'))
                                                <div class="help-block">
                                                    {{ $errors->first('billing_address') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if (config('custom.japan'))
                                        <input type="hidden" name="billing_country_id" value="{{ Acelle\Model\Country::getJapan()->id }}" />
                                    @else
                                        <div class="col-md-6">
                                            <div class="form-group {{ $errors->has('billing_country_id') ? 'has-error' : '' }}">
                                                <label>
                                                    {{ trans('messages.country') }}
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div>
                                                    <select name="billing_country_id" class="select select-search required required select2-hidden-accessible" data-select2-id="1" tabindex="-1" aria-hidden="true">
                                                        <option value="">{{ trans('messages.select_country') }}</option>
                                                        @foreach (Acelle\Model\Country::getSelectOptions() as $option)
                                                            <option {{ $invoice->billing_country_id == $option['value'] ? 'selected' : '' }} value="{{ $option['value'] }}">{{ $option['text'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @if ($errors->has('billing_country_id'))
                                                    <div class="help-block">
                                                        {{ $errors->first('billing_country_id') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="d-flex align-items-center">
                        <div>
                            <a href="{{ action('CheckoutController@cart', [
                                'invoice_uid' => $invoice->uid,
                            ]) }}" class="btn btn-light">
                                <span class="material-symbols-rounded">arrow_back</span>
                                {{ trans('messages.checkout.back_to_cart') }}
                            </a>
                        </div>
                        <div class="ms-auto">
                            <button href="{{ action('CheckoutController@billingAddress', [
                                'invoice_uid' => $invoice->uid,
                            ]) }}" class="btn btn-primary">
                                {{ trans('messages.next') }}
                                <span class="material-symbols-rounded">arrow_forward</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
        

@endsection
