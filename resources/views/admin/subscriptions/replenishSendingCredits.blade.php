@extends('layouts.popup.medium')

@section('content')
	<div class="row">
        <div class="col-md-12">
            <form id="ReplenishForm" enctype="multipart/form-data" action="{{ action('Admin\SubscriptionController@replenishSendingCredits', $subscription->uid) }}" method="POST" class="">
                {{ csrf_field() }}

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group {{ $errors->has('credits') ? 'has-error' : '' }}">
                            <label>
                                {{ trans('messages.subscription.replenish.send_email_credits', [ 'int' => ($remaining == -1) ? '∞' : number_with_delimiter($remaining)   ]) }}
                            </label>
                            <div>
                                <input value=""
                                    required
                                    type="number"
                                    name="credits" class="form-control">
                            </div>
                            @if ($errors->has('credits'))
                                <div class="help-block">
                                    {{ $errors->first('credits') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary bg-grey">{{ trans('messages.ok') }}</button>
            </form>
        </div>
    </div>

    <script>
        $('#ReplenishForm').submit(function(e) {
            e.preventDefault();

            var url = $(this).attr('action');
            var data = $(this).serialize();

            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                statusCode: {
                    // validate error
                    400: function (res) {
                        replenishCredit.popup.loadHtml(res.responseText);
                    }
                },
                success: function (response) {
                    replenishCredit.popup.hide();

                    // notify
                    notify({
                        type: 'success',
                        title: '{!! trans('messages.notify.success') !!}',
                        message: response.message
                    });
                }
            });
        });
    </script>
@endsection