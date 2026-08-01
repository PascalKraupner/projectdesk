<?php

namespace App\Http\Controllers;

use App\Http\Requests\Invoice\StoreInvoiceItemRequest;
use App\Http\Requests\Invoice\UpdateInvoiceItemRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;

class InvoiceItemController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    public function store(StoreInvoiceItemRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->invoiceService->addItem($invoice, $request->validated());

        return back();
    }

    public function update(UpdateInvoiceItemRequest $request, InvoiceItem $invoiceItem): RedirectResponse
    {
        $this->invoiceService->updateItem($invoiceItem, $request->validated());

        return back();
    }

    public function destroy(InvoiceItem $invoiceItem): RedirectResponse
    {
        $this->invoiceService->removeItem($invoiceItem);

        return back();
    }
}
