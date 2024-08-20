@extends('layouts.core.frontend', [
    'menu' => 'campaign',
])

@section('title', trans('messages.campaigns') . " - " . trans('messages.confirm'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("CampaignController@index") }}">{{ trans('messages.campaigns') }}</a></li>
            <li class="breadcrumb-item" style="font-weight: bold">{{ $campaign->name }}</li>
        </ul>
    </div>

@endsection

@section('content')

    <div class="confirm-campaign-box">

        <div class="head">
            <h2 class="text-semibold mb-2">Measurement</h2>
            <p>Your last delivery session performance</p>
        </div>

        <ul class="modern-listing">
            <li class="d-flex align-items-center">

                <!-- {{ $count = $campaign->subscribersCount() }} -->

                <span class="fs-4 me-4">
                    @if ($count)
                        <i class="material-symbols-rounded text-success">task_alt</i>
                    @else
                        <span class="material-symbols-rounded text-danger">highlight_off</span>
                    @endif
                </span>
                <div class="me-auto">
                    <h4><strong>{{ number_with_delimiter($count) }}</strong> contacts in total</h4>
                    <p>
                        From list "{!! $campaign->displayRecipients() !!}"
                    </p>
                </div>

                <a href="#" class="btn btn-secondary">{{ trans('messages.edit') }}</a>
            </li>

            <li class="d-flex align-items-center">
                <span class="fs-4 me-4">
                    <i class="material-symbols-rounded text-success">task_alt</i>
                </span>
                <div class="me-auto">
                    <h4><strong>{{ $debug['start_at'] ? \Carbon\Carbon::parse($debug['start_at'])->diffForHumans() : 'not started' }}</strong></h4>
                    <p>
                        Last session started at {{ $debug['start_at'] ? $campaign->customer->formatDateTime(\Carbon\Carbon::parse($debug['start_at']), 'datetime_full_with_timezone') : 'N/A' }}
                        • Last activity at: {{ $debug['last_activity_at'] ? $campaign->customer->formatDateTime(\Carbon\Carbon::parse($debug['last_activity_at']), 'datetime_full_with_timezone') : 'N/A' }} ({{ $debug['last_activity_at'] ? \Carbon\Carbon::parse($debug['last_activity_at'])->diffForHumans() : 'N/A' }})
                    </p>
                </div>

                <a href="#" class="btn btn-secondary">{{ trans('messages.edit') }}</a>
            </li>

            <li class="d-flex align-items-center">
                <span class="fs-4 me-4">
                    <i class="material-symbols-rounded text-success">task_alt</i>
                </span>
                <div class="me-auto">
                    <h4><strong>{{ \Carbon\CarbonInterval::seconds($debug['total_time'])->cascade()->forHumans(); }}</strong> passed</h4>
                    <p>
                        @if (!is_null($debug['finish_at']))
                        	Finished {{ \Carbon\Carbon::parse($debug['finish_at'])->diffForHumans() }} at {{ $debug['finish_at'] }}
                        @elseif (!is_null($debug['last_message_sent_at']))
                        	Session not yet finish, last message sent {{ \Carbon\Carbon::parse($debug['last_message_sent_at'])->diffForHumans() }}
                        @else
                            Session not yet finish...
                        @endif
                    </p>
                </div>

                <a href="#" class="btn btn-secondary">{{ trans('messages.edit') }}</a>
            </li>

            <li class="d-flex align-items-center">
                <span class="fs-4 me-4">
                    <i class="material-symbols-rounded text-success">task_alt</i>
                </span>
                <div class="me-auto">
                    <h4><strong>{{ number_with_delimiter($debug['send_message_count']) }}</strong> messages sent</h4>
                    <p>
                        Since session started
                    </p>
                </div>

                <a href="#" class="btn btn-secondary">{{ trans('messages.edit') }}</a>
            </li>

            <li class="d-flex align-items-center">
                <span class="fs-4 me-4">
                    <i class="material-symbols-rounded text-success">task_alt</i>
                </span>
                <div class="me-auto">
                    <h4><strong>{{ $debug['send_message_avg_time'] ? number_with_delimiter($debug['send_message_avg_time']) : 0 }}</strong> seconds per message avg.</h4>
                    <p>
                        Best time {{ $debug['send_message_min_time'] ? number_with_delimiter($debug['send_message_min_time']) : 0 }} seconds • Worst time {{ $debug['send_message_max_time'] ? number_with_delimiter($debug['send_message_max_time']) : 0 }} seconds
                    </p>
                    <p>
                        Average {{ $debug['send_message_avg_time'] ? number_with_delimiter($debug['send_message_avg_time']) : 0 }} = { Prepare {{  $debug['send_message_prepare_avg_time'] ? number_with_delimiter($debug['send_message_prepare_avg_time']) : 0 }} &rarr;
                        Obtain lock {{ isset($debug['send_message_lock_avg_time']) ? number_with_delimiter($debug['send_message_lock_avg_time']) : 0 }} &rarr;
                        Deliver {{ $debug['send_message_delivery_avg_time'] ? number_with_delimiter($debug['send_message_delivery_avg_time']) : 0 }} }
                    </p>
                </div>

                <a href="#" class="btn btn-secondary">{{ trans('messages.edit') }}</a>
            </li>

            <li class="d-flex align-items-center">
                <span class="fs-4 me-4">
                    <i class="material-symbols-rounded text-success">task_alt</i>
                </span>
                <div class="me-auto">
                    <h4><strong style="color:#19c714">{{ $debug['messages_sent_per_second'] ? number_with_delimiter($debug['messages_sent_per_second']) : 0 }}</strong> messages per second </h4>
                    <p>
                        Boost rate {{ $debug['send_message_avg_time'] ? number_with_delimiter($debug['messages_sent_per_second'] / ( 1 / $debug['send_message_avg_time'])) : 0  }}x
                    </p>
                </div>

                <a href="#" class="btn btn-secondary">{{ trans('messages.edit') }}</a>
            </li>
        </ul>
    </div>
@endsection
