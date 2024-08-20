@if ($transactions->count() > 0)
    <table class="table table-box pml-table table-log mt-10">
        <tr>
            <th width="200px">{{ trans('messages.created_at') }}</th>
            <th>{{ trans('messages.message') }}</th>
            <th>{{ trans('messages.transaction.amount') }}</th>
            <th>{{ trans('messages.transaction.method') }}</th>
            <th>{{ trans('messages.status') }}</th>
        </tr>
        @forelse ($transactions as $key => $transaction)
            <tr>
                <td>
                    <span class="no-margin kq_search">
                        {{ Auth::user()->admin->formatDateTime($transaction->created_at, 'datetime_full') }}
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
	@include('elements/_per_page_select', ["items" => $transactions])
    
@elseif (!empty(request()->keyword) || !empty(request()->filters))
	<div class="empty-list">
		<span class="material-symbols-rounded">assignment_turned_in</span>
		<span class="line-1">
			{{ trans('messages.no_search_result') }}
		</span>
	</div>
@else
	<div class="empty-list">
		<span class="material-symbols-rounded">assignment_turned_in</span>
		<span class="line-1">
			{{ trans('messages.subscription_empty_line_1_admin') }}
		</span>
	</div>
@endif
