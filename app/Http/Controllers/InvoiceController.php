<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly InvoicePdfService $pdfService,
    ) {}

    public function index(Request $request): Response
    {
        $status = InvoiceStatus::tryFrom((string) $request->query('status'));

        return Inertia::render('Invoice/Index', [
            'invoices' => $this->invoiceService->all($status)->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status->value,
                'client_name' => $invoice->recipient_name,
                'client_id' => $invoice->client_id,
                'issue_date' => $invoice->issue_date->toIso8601String(),
                'due_date' => $invoice->due_date->toIso8601String(),
                'total_amount' => (float) $invoice->total_amount,
                'currency' => $invoice->currency,
            ])->all(),
            'statuses' => array_map(
                fn (InvoiceStatus $s) => $s->value,
                InvoiceStatus::cases(),
            ),
            'filter' => $status?->value,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $invoice = isset($data['project_id'])
            ? $this->invoiceService->createForProjectId((int) $data['project_id'], $request->from(), $request->to())
            : $this->invoiceService->createForClientId((int) $data['client_id'], $request->from(), $request->to());

        $overlapping = $this->invoiceService
            ->overlapping($invoice->client, $request->from(), $request->to())
            ->reject(fn (Invoice $other) => $other->is($invoice));

        return redirect()->route('invoices.show', $invoice)->with(
            'warning',
            $overlapping->isEmpty() ? null : 'This period is already covered by '.$overlapping->pluck('number')->implode(', ').'.',
        );
    }

    public function show(Invoice $invoice): Response
    {
        $invoice->load(['items', 'client:id,name']);

        return Inertia::render('Invoice/Show', [
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status->value,
                'editable' => $invoice->status->isEditable(),
                'discardable' => $this->invoiceService->isDiscardable($invoice),
                'issue_date' => $invoice->issue_date->toDateString(),
                'due_date' => $invoice->due_date->toDateString(),
                'payment_terms_days' => $invoice->payment_terms_days,
                'currency' => $invoice->currency,
                'total_amount' => (float) $invoice->total_amount,
                'period_start' => $invoice->period_start?->toDateString(),
                'period_end' => $invoice->period_end?->toDateString(),
                'client_id' => $invoice->client_id,
                'recipient' => [
                    'name' => $invoice->recipient_name,
                    'contact_person' => $invoice->recipient_contact_person,
                    'street' => $invoice->recipient_street,
                    'postal_code' => $invoice->recipient_postal_code,
                    'city' => $invoice->recipient_city,
                    'country' => $invoice->recipient_country,
                    'vat_id' => $invoice->recipient_vat_id,
                ],
                'items' => $invoice->items->map(fn ($item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'service_date' => $item->service_date->toDateString(),
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => (float) $item->unit_price,
                    'amount' => (float) $item->amount,
                ])->all(),
            ],
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->invoiceService->updateInvoice($invoice, $request->validated());

        return back();
    }

    public function issue(Invoice $invoice): RedirectResponse
    {
        $this->invoiceService->issue($invoice);

        return back();
    }

    public function pay(Invoice $invoice): RedirectResponse
    {
        $this->invoiceService->markPaid($invoice);

        return back();
    }

    public function cancel(Invoice $invoice): RedirectResponse
    {
        $this->invoiceService->cancel($invoice);

        return back();
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->invoiceService->discard($invoice);

        return redirect()->route('invoices.index');
    }

    public function pdf(Invoice $invoice): HttpResponse
    {
        return $this->pdfService->download($invoice);
    }
}
