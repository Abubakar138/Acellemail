<h2 class="mt-0 mb-3">{{ trans('messages.campaign_headers') }}</h2>
<p>{{ trans('messages.campaign_header.intro') }}</p>

@if ($campaign->campaignHeaders()->count())
    <div class="row">
        <div class="col-md-6">
            <ul class="key-value-list mt-2">
                @foreach ($campaign->campaignHeaders as $campaignHeader)
                    <li class="d-flex align-items-center">
                        <div class="list-media mr-4">
                            <i class="material-symbols-rounded text-muted">code</i>
                        </div>
                        <div class="values mr-auto">
                            <label>
                                {{ $campaignHeader->name }}
                            </label>
                            <div class="value">
                                {{ $campaignHeader->value }}
                            </div>
                        </div>
                        <div class="list-action">
                            <a header-control="remove" href="{{ action('CampaignController@campaignHeaderRemove', [
                                'campaign_header_uid' => $campaignHeader->uid,
                            ]) }}" class="btn btn-default btn-sm">
                                <i class="material-symbols-rounded text-muted">delete</i>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif    
    <div class="">
        <a id="CampaignHeaderAddPopupButton" href="javascript:;" class="btn btn-default">
            <span class="material-symbols-rounded">add</span>
            {{ trans('messages.campaign_header.add') }}
        </a>
    </div>

<script>
    var CampaignCampaignHeader = class {

        constructor() {
            this.addPopup = new Popup({
                url: '{{ action('CampaignController@campaignHeaderAdd', $campaign->uid) }}'
            });
        }

        remove(url) {
            new Dialog('confirm', {
                message: '{{ trans('messages.campaign_header.remove.confirm') }}',
                ok: function() {
                    addMaskLoading();
                    $.ajax({
                        method: "POST",
                        url: url,
                        data: {
                            _token: CSRF_TOKEN
                        }
                    })
                    .done(function( response ) {
                        removeMaskLoading();

                        // notify
                        notify(response.status, '{{ trans('messages.notify.success') }}', response.message);

                        campaign_CampaignHeader.box.load();
                    });   
                }
            });
                      
        }
    }

    $(function() {
        campaignCampaignHeader = new CampaignCampaignHeader();

        $('#CampaignHeaderAddPopupButton').on('click', function() {
            campaignCampaignHeader.addPopup.load();
        });

        $('#CampaignHeaderEditButton').on('click', function() {
            campaignCampaignHeader.addPopup.load();
        });
        
        $('[header-control="remove"]').on('click', function(e) {
            e.preventDefault();

            var url = $(this).attr('href');
            campaignCampaignHeader.remove(url);
        });
    });
</script>