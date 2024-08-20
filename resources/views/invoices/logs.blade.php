@extends('layouts.popup.medium')

@section('content')
	<div class="row">
        <div class="col-md-12">
            <h3>{{ trans('messages.invoice.invoice_code') }} <span class="badge bg-secondary fs-5">#{{ $invoice->uid }}</span></h3>
            <h5 class="mt-0 mt-4">{{ trans('messages.invoice.transactions') }}</h5>
            
            <table class="table table-box pml-table table-log mt-10">
                <tr>
                    <th width="200px">{{ trans('messages.created_at') }}</th>
                    <th>{{ trans('messages.message') }}</th>
                    <th>{{ trans('messages.transaction.amount') }}</th>
                    <th>{{ trans('messages.transaction.method') }}</th>
                    <th>{{ trans('messages.status') }}</th>
                </tr>
                @forelse ($invoice->getTransactions() as $key => $transaction)
                    <tr>
                        <td>
                            <span class="no-margin kq_search">
                                {{ Auth::user()->customer->formatDateTime($transaction->created_at, 'datetime_full') }}
                            </span>
                        </td> 
                        <td>
                            <span class="no-margin kq_search">
                                {!! trans('messages.transaction_for_invoice', [
                                    'uid' => $transaction->invoice->uid
                                ]) !!}
                            </span>
                        </td> 
                        <td>
                            <span class="no-margin kq_search">
                                {!! format_price($transaction->invoice->total(), $transaction->invoice->currency->format) !!}
                            </span>
                        </td> 
                        <td>
                            <span class="no-margin kq_search" style="text-transform: capitalize;">
                                {{ $transaction->method }}
                            </span>
                        </td> 
                        <td>
                            <span class="no-margin kq_search">
                                <span {!! $transaction->error ? 'title="'.strip_tags($transaction->error).'"' : '' !!} class="xtooltip label label-{{ $transaction->status }}" style="white-space: nowrap;">
                                    {{ trans('messages.transaction.' . $transaction->status) }}
                                </span>
                            </span>
                        </td>                                
                    </tr>
                @empty
                    <tr>
                        <td class="text-center" colspan="5">
                            {{ trans('messages.subscription.logs.empty') }}
                        </td>
                    </tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection