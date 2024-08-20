@php $menu = $menu ?? false @endphp

<div class="row">
    <div class="col-md-12">
        <div class="tabbable pb-2">
            <ul class="nav nav-tabs nav-tabs-top nav-underline mb-4 border-bottom-0">
                <li class="nav-item {{ $menu == 'general' ? 'active' : '' }}">
                    <a href="{{ action("SubscriptionController@index") }}" class="nav-link px-0 bordxer me-5 mb-4 border-end-0 py-1
                        {{ $menu == 'general' ? '' : 'bg-lightx text-mutedx' }}
                        "
                        style="border-radius:0;"
                    >
                        @if (Auth::user()->customer->getCurrentActiveSubscription())
                            <span class="d-flex align-items-center py-1" style="height:70px;">
                                <span>
                                    <span class="d-block fw-bold">{{ Auth::user()->customer->getCurrentActiveSubscription()->plan->name }}</span>
                                    <span class="d-block fw-normal small text-muted">{{ trans('messages.subscription') }}</span>
                                </span>
                                <span class="ms-4">
                                    <div class="cal">
                                        <div class="month">{{ Auth::user()->customer->getCurrentActiveSubscription()->current_period_ends_at->format('F') }}</div>
                                        <div class="date">{{ Auth::user()->customer->getCurrentActiveSubscription()->current_period_ends_at->format('d') }}</div>
                                    </div>
                                </span>
                            </span>
                        @else
                            <span class="d-flex align-items-center py-1" style="height:70px;">
                                <span>
                                    <span class="d-block fw-bold">--</span>
                                    <span class="d-block fw-normal small text-muted">{{ trans('messages.subscription') }}</span>
                                </span>
                                <span class="ms-4">
                                    <div class="cal">
                                        <div class="month">--</div>
                                        <div class="date">--</div>
                                    </div>
                                </span>
                            </span>
                        @endif
                    </a>
                </li>

                @if (\Acelle\Helpers\isSendingCreditPluginActive() && Auth::user()->customer->getCurrentActiveSubscription())
                    <li class="nav-item {{ $menu == 'sending_credit_plan' ? 'active' : '' }}">
                        <a href="{{ action("SendingCreditPlanController@index") }}" class="nav-link px-0 boxrder me-5 mb-4 py-1
                            {{ $menu == 'sending_credit_plan' ? '' : 'bg-lightx text-mutedx' }}"
                            style="border-radius:0;"
                        >
                            <span class="d-flex align-items-center py-1" style="height:70px;">
                                <span>
                                    <span class="d-block">
                                        <span class=" fw-bold">
                                            {{ number_with_delimiter(Auth::user()->customer->getCurrentActiveSubscription()->getSendEmailCreditTracker()->getRemainingCredits()) }}
                                        </span>
                                    </span>
                                    <span class="d-block fw-normal small text-muted">
                                        <span>{{ trans('messages.sending_credit_plan.credits.menu') }}</span>
                                    </span>
                                </span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item {{ $menu == 'email_verification_plan' ? 'active' : '' }}">
                        <a href="{{ action("EmailVerificationPlanController@index") }}" class="nav-link px-0 boxrder me-5 mb-4 py-1
                            {{ $menu == 'email_verification_plan' ? '' : 'bg-lightx text-mutedx' }}"
                            style="border-radius:0;"
                        >
                            <span class="d-flex align-items-center py-1" style="height:70px;">
                                <span>
                                    <span class="d-block">
                                        <span class=" fw-bold">
                                            {{ number_with_delimiter(Auth::user()->customer->getCurrentActiveSubscription()->getVerifyEmailCreditTracker()->getRemainingCredits()) }}
                                        </span>
                                    </span>
                                    <span class="d-block fw-normal small text-muted">
                                        <span>{{ trans('messages.email_verification_plan.credits.menu') }}</span>
                                    </span>
                                </span>
                            </span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
