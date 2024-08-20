<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Acelle\Model\Invoice;

class InvoiceController extends Controller
{
    public function download(Request $request)
    {
        $invoice = Invoice::findByUid($request->uid);

        return \Response::make($invoice->exportToPdf(), 200, [
            'Content-type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-'.$invoice->uid.'.pdf"',
        ]);
    }

    public function logs(Request $request)
    {
        // init
        $invoice = Invoice::findByUid($request->invoice_uid);

        return view('invoices.logs', [
            'invoice' => $invoice,
        ]);
    }

    public function delete(Request $request)
    {
        // init
        $invoice = Invoice::findByUid($request->invoice_uid);

        if ($request->user()->customer->can('delete', $invoice)) {
            $invoice->cancel();
        }

        echo trans('messages.invoice.deleted');
    }
}
