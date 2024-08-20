<table class="table table-box pml-table table-log mt-0">
    <tr>
        <th width="130px">{{ trans('messages.invoice.id') }}</th>
        <th>{{ trans('messages.invoice.title') }}</th>
        <th>{{ trans('messages.balance.adjustment') }}</th>
        {{-- <th>{{ trans('messages.invoice.amount') }}</th> --}}
        <th>{{ trans('messages.invoice.status') }}</th>
        <th></th>
    </tr>
    @forelse ($invoices as $key => $invoice)
        @php
            $billInfo = $invoice->mapType()->getBillingInfo();
        @endphp
        <tr>
            <td class="pe-4">
                <div>
                    <span class="text-semibold">#{{ $invoice->uid }}</span>
                </div>
                <span class="no-margin kq_search">
                    {{ Auth::user()->customer->formatDateTime($invoice->created_at, 'datetime_full') }}
                </span>
            </td>
            <td width="40%">
                <span class="no-margin kq_search font-weight-semibold">
                    {!! $billInfo['title'] !!}
                </span>
                <div class="text-muted">
                    {!! $billInfo['description'] !!}
                </div>
            </td>
            <td>
                @if ($invoice->total() == 0)
                    <span class="no-margin kq_search text-nowrap">
                        {{ format_price($invoice->total(), $invoice->currency->format) }}
                    </span>
                @else
                    <span class="no-margin kq_search text-danger text-nowrap">
                        -{{ format_price($invoice->total(), $invoice->currency->format) }}
                    </span>
                @endif
            </td>
            {{-- <td>
                <span class="no-margin kq_search">
                    {{ $billInfo['total'] }}
                </span>
            </td> --}}
            <td>
                <span class="no-margin kq_search">
                    <span class="label bg-{{ $invoice->status }}" style="white-space: nowrap;">
                        {{ trans('messages.invoice.status.' . $invoice->status) }}
                    </span>
                </span>
            </td>
            <td>
                @if ($invoice->isPaid())
                    <a class="btn btn-light btn-icon text-nowrap xtooltip" title="{{ trans('messages.download') }}" target="_blank" href="{{ action('InvoiceController@download', [
                        'uid' => $invoice->uid,
                    ]) }}">
                        <i class="material-symbols-rounded">download</i>
                    </a>
                @endif
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

@include('elements/_per_page_select', ["items" => $invoices])