@extends('layouts.popup.small')

@section('content')
	<div class="row">
        <div class="col-md-12">
            <form action="{{ action("SubscriberController@addTag", $list->uid) }}"
                method="POST" class="add-tag"
            >
                {{ csrf_field() }}

                @foreach (request()->uids as $uid)
                    <input type="hidden" name="uids[]" value="{{ $uid }}" />
                @endforeach

                <input type="hidden" name="select_tool" value="{{ request()->select_tool }}" />

                <h3 class="mb-3">{{ trans('messages.subscriber.add_tag') }}</h3>
                <p>{!! trans('messages.subscriber.add_tag.wording', [
                    'count' => number_with_delimiter($subscribers->count(), $precision = 0),
                ]) !!}</p>
                    
                    @include('helpers.form_control', [
                        'type' => 'select_tag',
                        'class' => '',
                        'label' => '',
                        'name' => 'tags[]',
                        'value' => [],
                        'options' => [],
                        'rules' => ['tags' => 'required'],
                        'multiple' => 'true',
                        'placeholder' => trans('messages.subscriber.add_tag.choose_tags'),
                    ])

                <div class="mt-4 pt-3">
                    <button class="btn btn-secondary">{{ trans('messages.save') }}</button>
                </div>
        </div>
    </div>
    
    <script>
        $('form.add-tag').submit(function(e) {
            e.preventDefault();
            
            var form = $(this);
            var data = form.serialize();
            var url = form.attr('action');
            
            addMaskLoading('{{ trans('messages.subscriber.add_tag.loading') }}');

            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                globalError: false,
                statusCode: {
                    // validate error
                    400: function (res) {
                        addTagManager.popup.loadHtml(res.responseText);

                        // remove masking
                        removeMaskLoading();
                    }
                },
                success: function (res) {
                    // hide popup
                    addTagManager.popup.hide();

                    // notify
                    notify('success', '{{ trans('messages.notify.success') }}', res.message);

                    // remove masking
                    removeMaskLoading();

                    // reload list
                    SubscribersIndex.getList().load();
                }
            });    
        });
    </script>
@endsection
