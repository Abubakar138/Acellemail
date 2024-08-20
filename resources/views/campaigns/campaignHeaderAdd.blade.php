@extends('layouts.popup.small')

@section('title')
    {{ trans('messages.campaign_header.add') }}
@endsection

@section('content')
    <form id="CampaignHeaderForm" action="{{ action('CampaignController@campaignHeaderAdd', $campaign->uid) }}" method="POST">
        {{ csrf_field() }}

        <div class="form-group {{ $errors->has('name') ? 'has-error' : '' }}"> 
            <label class="form-label required" for="message">{{ trans('messages.campaign_header.name') }}</label>
            <input type="text" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                name="name" value="{{ $campaignHeader->name }}"
            />
    
            @if ($errors->has('name'))
                <div class="help-block"> {{ $errors->first('name') }} </div>
            @endif
        </div>

        <div class="form-group {{ $errors->has('value') ? 'has-error' : '' }}"> 
            <label class="form-label required" for="message">{{ trans('messages.campaign_header.value') }}</label>
            <input type="text" class="form-control {{ $errors->has('value') ? 'is-invalid' : '' }}"
                name="value" value="{{ $campaignHeader->value }}"
            />
    
            @if ($errors->has('value'))
                <div class="help-block"> {{ $errors->first('value') }} </div>
            @endif
        </div>

        <div class="mt-4 text-center">
            <button type="submit" class="btn btn-secondary me-1">{{ trans('messages.campaign_header.save') }}</button>
            <button type="button" class="btn btn-default close">{{ trans('messages.campaign_header.close') }}</button>
        </div>
    </form>

    <script>
        $(function() {
            $('#CampaignHeaderForm').on('submit', function(e) {
                e.preventDefault();

                var url = $(this).attr('action');
                var data = $(this).serialize();

                addMaskLoading();

                // 
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: data,
                    globalError: false,
                    statusCode: {
                        // validate error
                        400: function (res) {
                            campaignCampaignHeader.addPopup.loadHtml(res.responseText);

                            // remove masking
                            removeMaskLoading();
                        }
                    },
                    success: function (response) {
                        removeMaskLoading();

                        // notify
                        notify(response.status, '{{ trans('messages.notify.success') }}', response.message);

                        campaignCampaignHeader.addPopup.hide();

                        campaign_CampaignHeader.box.load();
                    }
                });
            });
        });
    </script>
@endsection