<div class="row">
    <div class="col-md-12">
        <div class="checkout-wz">
            <a href="{{ action('CheckoutController@cart', [
                'invoice_uid' => $invoice->uid,
            ]) }}" class="checkout-wz-item {{ $step == 'cart' ? 'active' : '' }}">
                <div class="checkout-wz-item-icon">
                    <span>
                        <span class="material-symbols-rounded">shopping_cart</span>
                    </span>
                </div>
                <div class="checkout-wz-item-content">
                    <p class="mb-0 text-semibold">
                        {{ trans('messages.checkout.cart') }}
                    </p>
                    <p class="mb-0 text-muted small">
                        {{ $invoice->title }}
                    </p>
                </div>
            </a>
            <div class="checkout-wz-arrow">
                <span class="material-symbols-rounded">more_horiz</span>
            </div>
            <a href="{{ action('CheckoutController@billingAddress', [
                'invoice_uid' => $invoice->uid,
            ]) }}" class="checkout-wz-item {{ $step == 'address' ? 'active' : '' }}">
                <div class="checkout-wz-item-icon">
                    <span>
                        <span class="material-symbols-rounded">contact_mail</span>
                    </span>
                </div>
                <div class="checkout-wz-item-content">
                    <p class="mb-0 text-semibold">
                        {{ trans('messages.address') }}
                    </p>
                    <p class="mb-0 text-muted small">
                        {{ trans('messages.checkout.for_sending_bills') }}
                    </p>
                </div>
            </a>
            <div class="checkout-wz-arrow">
                <span class="material-symbols-rounded">more_horiz</span>
            </div>
            <a href="{{ action('CheckoutController@payment', [
                'invoice_uid' => $invoice->uid,
            ]) }}" class="checkout-wz-item {{ !$invoice->hasBillingInformation() ? 'pe-none' : '' }} {{ $step == 'payment' ? 'active' : '' }}">
                <div class="checkout-wz-item-icon">
                    <span>
                        <span class="material-symbols-rounded">payments</span>
                    </span>
                </div>
                <div class="checkout-wz-item-content">
                    <p class="mb-0 text-semibold">
                        {{ trans('messages.checkout.payment') }}
                    </p>
                    <p class="mb-0 text-muted small">
                        {{ trans('messages.checkout.select_method') }}
                    </p>
                </div>
            </a>
        </div>
    </div>
</div>