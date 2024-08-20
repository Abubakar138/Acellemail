@extends('layouts.popup.large')

@section('content')
    <h2>{{ trans('messages.list.webhook_integration') }}</h2>
    <p>{{ trans('messages.list.webhook.json.intro') }}</p>

    <ul class="nav nav-tabs nav-tabs-top nav-underline">
        <li class="nav-item">
            <a webhook-control="tab-link" class="nav-link active" href="{{ action('MailListController@webhookJson', [
                'uid' => $list->uid,
            ]) }}">
                <span class="material-symbols-rounded">data_object</span>
                {{ trans('messages.list.webhook.json_post') }}
            </a>
        </li>
        <li class="nav-item">
            <a webhook-control="tab-link" class="nav-link" href="{{ action('MailListController@webhookForm', [
                'uid' => $list->uid,
            ]) }}">
                <span class="material-symbols-rounded">dynamic_form</span>  {{ trans('messages.list.webhook.form_post') }}
            </a>
        </li>
    </ul>

    @include('lists.webhookExample', [
        'type' => 'json',
    ])

@endsection