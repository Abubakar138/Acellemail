<div id="CampaignHeaderBox" class="sub-section">   
    
</div>

<script>
    var Campaign_CampaignHeader = class {
        constructor() {
            this.box = new Box($('#CampaignHeaderBox'), '{{ action('CampaignController@campaignHeader', $campaign->uid) }}');
            this.box.load();
        }
    }

    $(function() {
        campaign_CampaignHeader = new Campaign_CampaignHeader();
    });
</script>