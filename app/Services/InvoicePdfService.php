<?php

namespace App\Services;

use App\Enums\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfService
{
    public function download(Invoice $invoice): Response
    {
        $document = $this->document($invoice);

        return Pdf::loadView('pdf.invoice', $document)
            ->setPaper(config('invoice.paper'))
            ->download($document['filename']);
    }

    public function filename(Invoice $invoice): string
    {
        return sprintf('INVOICE %s - %s.pdf', $invoice->number, config('invoice.issuer.name'));
    }

    /** @return array<string, mixed> */
    public function document(Invoice $invoice): array
    {
        $invoice->loadMissing('items');

        $issuer = config('invoice.issuer');
        $symbol = (Currency::tryFrom($invoice->currency) ?? Currency::EUR)->symbol();

        return [
            'filename' => $this->filename($invoice),
            'issuer' => $issuer,
            'issuer_address_line' => $this->addressLine(
                $issuer['street'], $issuer['postal_code'], $issuer['city']
            ),
            'recipient_lines' => array_values(array_filter([
                $invoice->recipient_name,
                $invoice->recipient_contact_person,
                $invoice->recipient_street,
                trim($invoice->recipient_postal_code.' '.$invoice->recipient_city) ?: null,
                $invoice->recipient_country,
            ])),
            'meta' => array_values(array_filter([
                ['label' => 'Rechnungsnummer:', 'value' => $invoice->number],
                ['label' => 'Rechnungsdatum:', 'value' => $invoice->issue_date->format('d.m.Y')],
                ['label' => 'Zahlungsbedingungen:', 'value' => $invoice->payment_terms_days.' Tage'],
                ['label' => 'Fälligkeitsdatum:', 'value' => $invoice->due_date->format('d.m.Y')],
                $invoice->recipient_vat_id
                    ? ['label' => 'USt-IdNr.:', 'value' => $invoice->recipient_vat_id]
                    : null,
            ])),
            'service_period' => $this->servicePeriod($invoice),
            'items' => $invoice->items->map(fn (InvoiceItem $item) => [
                'description' => $item->description,
                'date' => $item->service_date->format('d.m.Y'),
                'quantity' => $this->decimal((float) $item->quantity),
                'unit' => $item->unit,
                'unit_price' => $this->money((float) $item->unit_price, $symbol),
                'amount' => $this->money((float) $item->amount, $symbol),
            ])->all(),
            'total' => $this->money((float) $invoice->total_amount, $symbol),
            'small_business_note' => config('invoice.small_business_note'),
            'font_regular' => resource_path('fonts/OpenSans-Regular.ttf'),
            'font_bold' => resource_path('fonts/OpenSans-Bold.ttf'),
        ];
    }

    private function servicePeriod(Invoice $invoice): ?string
    {
        if (! config('invoice.show_service_period')) {
            return null;
        }

        if ($invoice->period_start === null || $invoice->period_end === null) {
            return null;
        }

        return sprintf(
            'Leistungszeitraum: %s – %s',
            $invoice->period_start->format('d.m.Y'),
            $invoice->period_end->format('d.m.Y'),
        );
    }

    private function addressLine(?string $street, ?string $postalCode, ?string $city): string
    {
        return implode(' • ', array_filter([$street, $postalCode, $city]));
    }

    private function money(float $value, string $symbol): string
    {
        return number_format($value, 2, ',', '.').' '.$symbol;
    }

    private function decimal(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }
}
