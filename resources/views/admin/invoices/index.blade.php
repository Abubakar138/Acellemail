@extends('layouts.core.backend', [
	'menu' => 'invoice',
])

@section('title', trans('messages.invoices'))

@section('page_header')

	<div class="page-title">
		<ul class="breadcrumb breadcrumb-caret position-right">
			<li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
		</ul>
		<h1>
			<span class="text-semibold"><span class="material-symbols-rounded">format_list_bulleted</span> {{ trans('messages.invoices') }}</span>
		</h1>
	</div>

@endsection

@section('content')
	<p>{{ trans('messages.invoice.wording') }}</p>

    <div class="sub-section">
        <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-12">
                <ul class="nav nav-tabs nav-underline mb-1" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="javascript:;" data-bs-toggle="tab" data-bs-target="#nav-invoices">
                            {{ trans('messages.invoices') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#nav-transactions" data-bs-toggle="tab">
                            {{ trans('messages.transactions') }}
                        </a>
                    </li>
                </ul>
    
                <div class="tab-content">
                    <div id="nav-invoices" class="tab-pane fade in show active">
                        <form id="invoiceListContainer" class="listing-form"
                            data-url="{{ action('Admin\InvoiceController@list') }}"
                            per-page="15"
                        >
                            <div class="d-flex top-list-controls top-sticky-content">
                                <div class="me-auto">
                                    <div class="filter-box">
                                        <span class="filter-group">
                                            <select class="select" name="sort_order">
                                                <option value="invoices.created_at">{{ trans('messages.created_at') }}</option>
                                            </select>
                                            <input type="hidden" name="sort_direction" value="desc" />
                    <button type="button" class="btn btn-light sort-direction" data-popup="tooltip" title="{{ trans('messages.change_sort_direction') }}" role="button" class="btn btn-xs">
                                                <span class="material-symbols-rounded desc">sort</span>
                                            </button>
                                        </span>
                                        <span class="filter-group">
                                            <select class="select" name="type">
                                                <option value="">{{ trans('messages.invoice.all_types') }}</option>
                                                @foreach (Acelle\Model\Invoice::getInvoiceTypeSelectOptions() as $option)
                                                    <option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
                                                @endforeach
                                            </select>
                                        </span>
                                        <span class="filter-group">
                                            <select class="select" name="status">
                                                <option value="">{{ trans('messages.invoice.all_statuses') }}</option>
                                                <option value="pending">{{ trans('messages.invoice.status.pending') }}</option>
                                                <option value="{{ Acelle\Model\Invoice::STATUS_NEW }}">{{ trans('messages.invoice.status.new') }}</option>
                                                <option value="{{ Acelle\Model\Invoice::STATUS_PAID }}">{{ trans('messages.invoice.status.paid') }}</option>
                                            </select>
                                        </span>
                                        <span class="me-2 input-medium">
                                            <select placeholder="{{ trans('messages.customer') }}"
                                                class="select2-ajax"
                                                name="customer_uid"
                                                data-url="{{ action('Admin\CustomerController@select2') }}">
                                            </select>
                                        </span>
                                    </div>
                                </div>
                            </div>
    
                            <div id="invoiceList">
                            </div>
                        </form>
    
                        <script>
                            var InvoiceList = {
                                getList: function() {
                                    return makeList({
                                        url: '{{ action('Admin\InvoiceController@list', [
                                            'show_customer' => true,
                                        ]) }}',
                                        container: $('#invoiceListContainer'),
                                        content: $('#invoiceList')
                                    });
                                }
                            };
    
                            $(document).ready(function() {
                                InvoiceList.getList().load();
                            });
                        </script>
                    </div>
                    <div id="nav-transactions" class="tab-pane fade">
                        <form id="transactionListContainer" class="listing-form"
                            data-url="{{ action('Admin\InvoiceController@transactionList') }}"
                            per-page="15"
                        >
                            <div class="d-flex top-list-controls top-sticky-content">
                                <div class="me-auto">
                                    <div class="filter-box">
                                        <span class="filter-group">
                                            <select class="select" name="sort_order">
                                                <option value="invoices.created_at">{{ trans('messages.created_at') }}</option>
                                            </select>
                                            <input type="hidden" name="sort_direction" value="desc" />
                    <button type="button" class="btn btn-light sort-direction" data-popup="tooltip" title="{{ trans('messages.change_sort_direction') }}" role="button" class="btn btn-xs">
                                                <span class="material-symbols-rounded desc">sort</span>
                                            </button>
                                        </span>
                                        <span class="me-2 input-medium">
                                            <select placeholder="{{ trans('messages.customer') }}"
                                                class="select2-ajax"
                                                name="customer_uid"
                                                data-url="{{ action('Admin\CustomerController@select2') }}">
                                            </select>
                                        </span>
                                        <span class="filter-group">
                                            <select class="select" name="status">
                                                <option value="">{{ trans('messages.transaction.all_statuses') }}</option>
                                                <option value="{{ Acelle\Model\Transaction::STATUS_PENDING }}">{{ trans('messages.transaction.status.pending') }}</option>
                                                <option value="{{ Acelle\Model\Transaction::STATUS_SUCCESS }}">{{ trans('messages.transaction.status.success') }}</option>
                                                <option value="{{ Acelle\Model\Transaction::STATUS_FAILED }}">{{ trans('messages.transaction.status.failed') }}</option>
                                            </select>
                                        </span>
                                    </div>
                                </div>
                            </div>
    
                            <div id="TransactionList">
                            </div>
                        </form>
    
                        <script>
                            var TransactionList = {
                                getList: function() {
                                    return makeList({
                                        url: '{{ action('Admin\InvoiceController@transactionList') }}',
                                        container: $('#transactionListContainer'),
                                        content: $('#TransactionList')
                                    });
                                }
                            };
    
                            $(document).ready(function() {
                                TransactionList.getList().load();
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
