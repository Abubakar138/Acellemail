@extends('layouts.popup.small')

@section('content')
	<div class="row">
        <div class="col-md-12">
            <h2 class="mt-0">{{ trans('messages.invoice.reject') }}</h2>
            <p>{{ trans('messages.invoice.reject.leave_note') }}</p>

            <form reject-control="form" action="{{ action('Admin\InvoiceController@reject', [
                'invoice_uid' => $invoice->uid,
            ]) }}" method="POST" class="form-validate-jquery reject-form">
		        {{ csrf_field() }}


                <div class="form-group control-textarea">
                    <textarea type="text" name="reason" class="form-control required {{ $errors->has('reason') ? 'is-invalid' : '' }}"></textarea>
                </div>

                <button class="btn btn-secondary">{{ trans('messages.invoice.reject') }}</button>
            </form>
        </div>
    </div>
        
    <script>
        $(function() {
            InvoiceReject.getForm().on('submit', function(e) {
                e.preventDefault();

                if (InvoiceReject.getForm().valid()) {
                    InvoiceReject.submit();
                }
            });
        });

        var InvoiceReject = {
            getForm: function() {
                return $('[reject-control="form"]');
            },

            submit: function() {
                var url = this.getForm().attr('action');
                var data = this.getForm().serialize();

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: data,
                    globalError: false,
                    statusCode: {
                        // validate error
                        400: function (res) {
                            InvoiceList.popup.loadHtml(res.responseText);
                        }
                    },
                    success: function (response) {
                        InvoiceList.popup.hide();

                        // notify
                        notify({
                            type: 'success',
                            title: '{!! trans('messages.notify.success') !!}',
                            message: response.message
                        });

                        InvoiceList.getList().load();
                    }
                });
            }
        }
    </script>
@endsection