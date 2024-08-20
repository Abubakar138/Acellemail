@extends('layouts.core.frontend', [
	'menu' => '',
])

@section('title', trans('messages.balance.your_balance'))

@section('head')
    <script type="text/javascript" src="{{ URL::asset('core/js/group-manager.js') }}"></script>
@endsection

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item active">{{ trans('messages.invoice.checkout') }}</li>
        </ul>
        <h1>
            <span class="text-semibold">
                <span class="material-symbols-rounded">payments</span>
                {{ trans('messages.checkout.payment') }}
            </span>
        </h1>
    </div>

@endsection

@section('content')
    @include('checkout._steps', [
        'step' => 'payment',
    ])

    <div class="row">
        <div class="col-md-8">
            <form id="PaymentForm" class="billing-address-form" action="{{ action('CheckoutController@checkout', [
                'invoice_uid' => $invoice->uid,
            ]) }}"
                method="POST"
            >
                {{ csrf_field() }}

                <div class="mt-5">
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
                                <span class="display-3">
                                    {{ trans('messages.checkout.select_payment') }}
                                </span>
                            </div>
                            <hr>
                            <div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="sub-section mb-30 choose-payment-methods">      
                                            @foreach(Acelle\Library\Facades\Billing::getEnabledPaymentGateways() as $gateway)
                                                <div payment-control="payment-row" class="choose-payment-method">
                                                    <div class="d-flex pt-3 pb-3 pl-2 choose-payment choose-payment-{{ $gateway->getType() }}">
                                                        <div class="text-end pe-2">
                                                            <div class="d-flex align-items-center form-group-mb-0 pt-1" style="width: 30px">
                                                                <div class="form-group control-radio2">
                                                                    <div class="radio custom-radio " data-popup="tooltip" title="">
                                                                        <label>
                                                                            <input payment-control="checker" {{ $paymentMethod == $gateway->getType() ? 'checked' : '' }} type="radio"
                                                                                name="payment_method"
                                                                                value="{{ $gateway->getType() }}">
                                                                            <div class="check"></div>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mr-auto pr-4">
                                                            <h5 class="font-weight-semibold mb-1">{{ $gateway->getName() }}</h5>
                                                            <p class="mb-0">
                                                                {{ $gateway->getShortDescription() }}
                                                            </p>
                                                        </div> 
                                                    </div>           
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="d-flex align-items-center">
                        <div>
                            <a href="{{ action('CheckoutController@checkout', [
                                'invoice_uid' => $invoice->uid,
                            ]) }}" class="btn btn-light">
                                <span class="material-symbols-rounded">arrow_back</span>
                                {{ trans('messages.checkout.go_back') }}
                            </a>
                        </div>
                        <div class="ms-auto">
                            <button href="{{ action('CheckoutController@billingAddress', [
                                'invoice_uid' => $invoice->uid,
                            ]) }}" class="btn btn-primary">
                                {{ trans('messages.checkout.pay') }}
                                <span class="material-symbols-rounded">arrow_forward</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-md-4 pt-5">
            <div id="orderBox">

            </div>
        </div>
    </div>
        
    <script>
        var TopUpPayment = {
            orderBox: null,

            getOrderBox: function() {
                if (this.orderBox == null) {
                    this.orderBox = new Box($('#orderBox'), '{{ action('CheckoutController@orderBox', [
                        'invoice_uid' => $invoice->uid,
                    ]) }}');
                }
                return this.orderBox;
            },

            getCheckedPaymentChecker: function() {
                return $('[payment-control="checker"]:checked');
            },

            getCheckedPaymentValue: function() {
                if (!this.getCheckedPaymentChecker().length) {
                    return null;
                }

                return this.getCheckedPaymentChecker().val();
            }
        }

        $(function() {
            // payment_method data
            if (TopUpPayment.getCheckedPaymentValue()) {
                TopUpPayment.getOrderBox().data = {
                    payment_method: TopUpPayment.getCheckedPaymentValue()
                };
            }

            TopUpPayment.getOrderBox().load();
            
            // prevent submit if no payment selected
            $('#PaymentForm').on('submit', function(e) {
                if (!TopUpPayment.getCheckedPaymentValue()) {
                    e.preventDefault();

                    new Dialog('alert', {
                        message: '{{ trans('messages.subscription.no_payment_method_selected') }}',
                        title: "{{ trans('messages.notify.error') }}"
                    });
                }
            });

            var manager = new GroupManager();
            
            // add item to manager
            $('[payment-control="payment-row"]').each(function() {
                manager.add({
                    radio: $(this).find('input[name=payment_method]'),
                    box: $(this)
                });
            });

            manager.bind(function(group, others) {
                var doCheck = function() {
                    var checked = group.radio.is(':checked');
                    
                    if (checked) {
                        others.forEach(function(other) {
                            other.box.removeClass("current");
                        });
                        group.box.addClass("current");

                        // set payment method
                        TopUpPayment.getOrderBox().data = {
                            payment_method: group.radio.val()
                        };
                        TopUpPayment.getOrderBox().load();
                    } else {
                        group.box.removeClass("current");
                    }
                };

                group.radio.on('change', function() {
                    doCheck();
                });

                group.box.on('click', function() {
                    group.radio.prop('checked', true);

                    doCheck();
                });

                doCheck();
            });
        });
        
    </script>
@endsection
