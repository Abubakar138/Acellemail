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
                {{ trans('messages.checkout.cart') }}
            </span>
        </h1>
    </div>

@endsection

@section('content')
    @include('checkout._steps', [
        'step' => 'cart',
    ])

    <div class="row">
        <div class="col-md-10">
            <div class="mt-4">
                <div class="border p-4 rounded shadow-sm bg-white">
                    <div class="">
                        <div class="d-flex align-items-center mb-3">
                            <p class="me-3 mb-0">
                                <span class="topup-header-icon">
                                    <span class="material-symbols-rounded">
                                        payments
                                    </span>
                                </span>
                            </p>
                            <span class="display-3">{{ format_price($invoice->subTotal(), $invoice->currency->format) }}</span>
                        </div>
                        <hr>
                        <p class="mb-0">{!! $invoice->description !!}</p>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <div class="d-flex align-items-center">
                    {{-- <div>
                        <a href="{{ action('Sms\SmsSenderIdController@index') }}" class="btn btn-light">
                            <span class="material-symbols-rounded">arrow_back</span>
                            {{ trans('messages.balance.change_amount') }}
                        </a>
                    </div> --}}
                    <div class="ms-auto">
                        <a href="{{ action('CheckoutController@billingAddress', [
                            'invoice_uid' => $invoice->uid,
                        ]) }}" class="btn btn-primary">
                            {{ trans('messages.next') }}
                            <span class="material-symbols-rounded">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
        

@endsection
