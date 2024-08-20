<h2 class="mt-4 mb-4">
    <span class="text-teal text-bold">{{ $email->readCache('SubscriberCount', 0) }}</span>
    {{ trans('messages.' . \Acelle\Library\Tool::getPluralPrase('recipient', $email->readCache('SubscriberCount', 0))) }}
</h2>

<div class="row fs-7">
    <div class="col-md-6 campaigns-summary">
        <div class="mb-2">
            <span class="text-bold d-inline-block text-end pe-3" style="width:120px">
                <span class="label bg-light">{{ trans('messages.from') }} <span class="material-symbols-rounded">alternate_email</span></span>
            </span>
            @if ($email->defaultMailList)
                <a href="{{ action('MailListController@overview', ['uid' => $email->defaultMailList->uid]) }}">
                    {!! $email->displayRecipients() !!}
                </a>
            @else
                {!! $email->displayRecipients() !!}
            @endif
        </div>
        <div class="mb-2">
            <span class="text-bold d-inline-block text-end pe-3" style="width:120px">
                <span class="label bg-light">{{ trans('messages.subject') }} <span class="material-symbols-rounded">subject</span></span></span>
            {{ $email->subject }}
        </div>
        <div class="mb-2">
            <span class="text-bold d-inline-block text-end pe-3" style="width:120px">
                <span class="label bg-light">{{ trans('messages.from_email') }} <span class="material-symbols-rounded">alternate_email</span></span></span>
                    <a href="mailto:{{ $email->from_email }}">{{ $email->from_email }}</a>
        </div>
        <div class="mb-2">
            <span class="text-bold d-inline-block text-end pe-3" style="width:120px">
                <span class="label bg-light">{{ trans('messages.from_name') }} <span class="material-symbols-rounded">subject</span></span></span>
            {{ $email->from_name }}
        </div>

    </div>
    <div class="col-md-6">
        <div class="mb-2">
            <span class="text-bold d-inline-block text-end pe-3" style="width:120px">
                <span class="label bg-light">{{ trans('messages.reply_to') }} <span class="material-symbols-rounded">alternate_email</span></span></span>
            <a href="mailto:{{ $email->reply_to }}">{{ $email->reply_to }}</a>
        </div>
        <div class="mb-2">
            <span class="text-bold d-inline-block text-end pe-3" style="width:120px">
                <span class="label bg-light">{{ trans('messages.updated_at') }} <span class="material-symbols-rounded">event</span></span></span>
            {{ Auth::user()->customer->formatDateTime($email->updated_at, 'datetime_full') }}
        </div>
        <div class="mb-2">
            <span class="text-bold d-inline-block text-end pe-3" style="width:120px">
                <span class="label bg-light">{{ trans('messages.run_at') }} <span class="material-symbols-rounded">event</span></span></span>
            {{ isset($email->run_at) ? Auth::user()->customer->formatDateTime($email->run_at, 'datetime_full') : "" }}
        </div>
        <div class="mb-2">
            <span class="text-bold d-inline-block text-end pe-3" style="width:120px">
                <span class="label bg-light">{{ trans('messages.delivery_at') }} <span class="material-symbols-rounded">event</span></span></span>
            {{ isset($email->delivery_at) ? Auth::user()->customer->formatDateTime($email->delivery_at, 'datetime_full') : "" }}
        </div>
    </div>
</div>
