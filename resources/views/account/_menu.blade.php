@php $menu = $menu ?? false @endphp

<div class="row">
    <div class="col-md-12">
        <div class="tabbable pb-2">
            <ul class="nav nav-tabs nav-tabs-top nav-underline mb-4">
                <li class="nav-item {{ $menu == 'profile' ? 'active' : '' }}">
                    <a href="{{ action("AccountController@profile") }}" class="nav-link">
                        <span class="material-symbols-rounded">face</span> {{ trans('messages.my_profile') }}
                    </a>
                </li>
                <li class="nav-item {{ $menu == 'contact' ? 'active' : '' }}">
                    <a href="{{ action("AccountController@contact") }}" class="nav-link">
                        <span class="material-symbols-rounded">maps_home_work</span> {{ trans('messages.contact_information') }}
                    </a>
                </li>
                @if (config('app.saas'))
                    <li class="nav-item {{ $menu == 'subscription' ? 'active' : '' }}">
                        <a href="{{ action("SubscriptionController@index") }}" class="nav-link position-relative {{ isset($tab) && $tab == 'subscription' ? 'active' : '' }}">
                            <span class="material-symbols-rounded">auto_mode</span> {{ trans('messages.subscription_and_credits') }}
                            @if (Auth::user()->customer->hasSubscriptionNotice())
                                <i class="material-symbols-rounded tabs-warning-icon text-danger">info</i>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item {{ $menu == 'billing' ? 'active' : '' }}">
                        <a href="{{ action("AccountController@billing") }}" class="nav-link">
                            <span class="material-symbols-rounded">credit_card</span> {{ trans('messages.billing') }}
                        </a>
                    </li>
                @endif
                <li class="nav-item {{ $menu == 'log' ? 'active' : '' }}">
                    <a href="{{ action("AccountController@logs") }}" class="nav-link">
                        <span class="material-symbols-rounded">restore</span> {{ trans('messages.logs') }}
                    </a>
                </li>
                <li class="nav-item {{ $menu == 'api' ? 'active' : '' }}">
                    <a href="{{ action("AccountController@api") }}" class="nav-link">
                        <span class="material-symbols-rounded">vpn_key</span> {{ trans('messages.api_token') }}
                    </a>
                </li>
                @if (\Acelle\Model\Setting::get('2fa_enable') == 'yes')
                    <li class="nav-item {{ $menu == '2fa' ? 'active' : '' }}">
                        <a href="{{ action("TwoFAController@index") }}" class="nav-link">
                            <span class="material-symbols-rounded">security</span> {{ trans('messages.2fa.two_factor_authentication') }}
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
