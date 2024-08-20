@extends('layouts.core.backend', [
    'menu' => 'monitor_campaign',
])

@section('title', trans('messages.campaigns'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
        </ul>
        <h1>
            <span class="material-symbols-rounded">format_list_bulleted</span> {{ trans('messages.campaigns') }}</span>
        </h1>
    </div>

@endsection

@section('content')
    <div id="CampaignsIndexContainer" class="listing-form top-sticky"
        per-page="{{ Acelle\Model\MailList::$itemsPerPage }}"
    >
        <div class="d-flex top-list-controls top-sticky-content">
            <div class="me-auto">
                @if ($campaigns->count() >= 0)
                    <div class="filter-box">
                        <span class="filter-group">
                            <span class="title text-semibold text-muted">{{ trans('messages.sort_by') }}</span>
                            <select class="select" name="sort_order">
                                <option value="name">{{ trans('messages.name') }}</option>
                                <option selected value="created_at">{{ trans('messages.created_at') }}</option>
                                <option value="updated_at">{{ trans('messages.updated_at') }}</option>
                            </select>
                            <input type="hidden" name="sort_direction" value="desc" />
<button type="button" class="btn btn-light sort-direction" data-popup="tooltip" title="{{ trans('messages.change_sort_direction') }}" role="button" class="btn btn-xs">
                                <span class="material-symbols-rounded desc">sort</span>
                            </button>
                        </span>
                        <span class="filter-group">
                            <span class="title text-semibold text-muted">{{ trans('messages.campaign.status') }}</span>
                            <select class="select" name="status">
                                <option value="">{{ trans('messages.campaign.status.all') }}</option>
                                @foreach (Acelle\Model\Campaign::statusSelectOptions() as $option)
                                    <option {{ request()->status == $option['value'] ? 'selected' : '' }} value="{{ $option['value'] }}">{{ $option['text'] }}</option>
                                @endforeach
                            </select>
                            <span class="me-2 input-medium">
                                <select placeholder="{{ trans('messages.customer.all_customers') }}"
                                    class="select2-ajax"
                                    name="customer_uid"
                                    data-url="{{ action('Admin\CustomerController@select2') }}">
                                </select>
                            </span>
                        </span>
                        <span class="text-nowrap search-container">
                            <input type="text" name="keyword" class="form-control search" value="{{ request()->keyword }}" value="{{ request()->keyword }}" placeholder="{{ trans('messages.type_to_search') }}" />
                            <span class="material-symbols-rounded">search</span>
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <div id="CampaignsIndexContent" class="pml-table-container">



        </div>
    </div>

    <script>
        var CampaignsIndex = {
            getList: function() {
                return makeList({
                    url: '{{ action('Admin\MonitorCampaignController@list') }}',
                    container: $('#CampaignsIndexContainer'),
                    content: $('#CampaignsIndexContent')
                });
            }
        };

        $(document).ready(function() {
            CampaignsIndex.getList().load();
        });
    </script>
@endsection
