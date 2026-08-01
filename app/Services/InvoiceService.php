<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Exceptions\InvoiceStateException;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        private readonly InvoiceNumberService $numberService,
    ) {}

    /** @return Collection<int, Invoice> */
    public function all(?InvoiceStatus $status = null): Collection
    {
        return Invoice::query()
            ->with('client:id,name')
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('number_sequence')
            ->get();
    }

    public function createForClient(Client $client, CarbonImmutable $from, CarbonImmutable $to): Invoice
    {
        return $this->create($client, null, $from, $to);
    }

    public function createForClientId(int $clientId, CarbonImmutable $from, CarbonImmutable $to): Invoice
    {
        return $this->createForClient(Client::findOrFail($clientId), $from, $to);
    }

    public function createForProjectId(int $projectId, CarbonImmutable $from, CarbonImmutable $to): Invoice
    {
        return $this->createForProject(Project::findOrFail($projectId), $from, $to);
    }

    public function createForProject(Project $project, CarbonImmutable $from, CarbonImmutable $to): Invoice
    {
        $project->loadMissing('client');

        if ($project->client === null) {
            throw ValidationException::withMessages([
                'project_id' => 'The project has no client.',
            ]);
        }

        return $this->create($project->client, $project, $from, $to);
    }

    /**
     * Overlaps are a warning, not a block: re-billing a period is legitimate.
     *
     * @return Collection<int, Invoice>
     */
    public function overlapping(Client $client, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Invoice::query()
            ->notCancelled()
            ->where('client_id', $client->id)
            ->whereNotNull('period_start')
            ->whereNotNull('period_end')
            ->where('period_start', '<=', $to->toDateString())
            ->where('period_end', '>=', $from->toDateString())
            ->orderByDesc('issue_date')
            ->get();
    }

    /** @param  array<string, mixed>  $data */
    public function addItem(Invoice $invoice, array $data): InvoiceItem
    {
        $this->assertEditable($invoice);

        $quantity = round((float) $data['quantity'], 2);
        $unitPrice = round((float) $data['unit_price'], 2);

        $item = $invoice->items()->create([
            'project_id' => $data['project_id'] ?? null,
            'description' => $data['description'],
            'service_date' => $data['service_date'] ?? $invoice->issue_date,
            'quantity' => $quantity,
            'unit' => $data['unit'] ?? config('invoice.default_unit'),
            'unit_price' => $unitPrice,
            'amount' => array_key_exists('amount', $data) && $data['amount'] !== null
                ? round((float) $data['amount'], 2)
                : round($quantity * $unitPrice, 2),
            'sort_order' => (int) ($invoice->items()->max('sort_order') + 1),
        ]);

        $this->recalculateTotal($invoice);

        return $item;
    }

    /** @param  array<string, mixed>  $data */
    public function updateItem(InvoiceItem $item, array $data): InvoiceItem
    {
        $invoice = $item->invoice;
        $this->assertEditable($invoice);

        $item->fill(array_intersect_key($data, array_flip([
            'description', 'service_date', 'unit', 'sort_order',
        ])));

        if (array_key_exists('quantity', $data)) {
            $item->quantity = round((float) $data['quantity'], 2);
        }

        if (array_key_exists('unit_price', $data)) {
            $item->unit_price = round((float) $data['unit_price'], 2);
        }

        // An explicit amount wins; otherwise it follows quantity times price.
        if (array_key_exists('amount', $data) && $data['amount'] !== null) {
            $item->amount = round((float) $data['amount'], 2);
        } elseif (array_key_exists('quantity', $data) || array_key_exists('unit_price', $data)) {
            $item->amount = round((float) $item->quantity * (float) $item->unit_price, 2);
        }

        $item->save();

        $this->recalculateTotal($invoice);

        return $item;
    }

    public function removeItem(InvoiceItem $item): void
    {
        $invoice = $item->invoice;
        $this->assertEditable($invoice);

        $item->delete();

        $this->recalculateTotal($invoice);
    }

    /** @param  array<string, mixed>  $data */
    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        $this->assertEditable($invoice);

        if (array_key_exists('issue_date', $data)) {
            $invoice->issue_date = CarbonImmutable::parse($data['issue_date'])->toDateString();
        }

        if (array_key_exists('payment_terms_days', $data)) {
            $invoice->payment_terms_days = (int) $data['payment_terms_days'];
        }

        $invoice->due_date = CarbonImmutable::parse($invoice->issue_date)
            ->addDays($invoice->payment_terms_days)
            ->toDateString();

        $invoice->save();

        return $invoice;
    }

    public function issue(Invoice $invoice): Invoice
    {
        if ($invoice->items()->count() === 0) {
            throw new InvoiceStateException('An invoice without items cannot be issued.');
        }

        return $this->transition($invoice, InvoiceStatus::Issued, 'issued_at');
    }

    public function markPaid(Invoice $invoice): Invoice
    {
        return $this->transition($invoice, InvoiceStatus::Paid, 'paid_at');
    }

    public function cancel(Invoice $invoice): Invoice
    {
        return $this->transition($invoice, InvoiceStatus::Cancelled, 'cancelled_at');
    }

    /**
     * Only the newest draft may be discarded outright. Removing any other one
     * would leave a hole in the sequence, which is what cancelling exists for.
     */
    public function isDiscardable(Invoice $invoice): bool
    {
        if ($invoice->status !== InvoiceStatus::Draft) {
            return false;
        }

        $prefix = (string) config('invoice.number.prefix');

        if (! str_starts_with($invoice->number, $prefix)) {
            return false;
        }

        $next = DB::table('invoice_number_sequences')->where('prefix', $prefix)->value('next_number');

        return $next !== null && $invoice->number_sequence === (int) $next - 1;
    }

    public function discard(Invoice $invoice): void
    {
        if (! $this->isDiscardable($invoice)) {
            throw new InvoiceStateException(
                "Invoice {$invoice->number} cannot be discarded, only cancelled: it is either not a draft or not the newest number."
            );
        }

        DB::transaction(function () use ($invoice) {
            $sequence = $invoice->number_sequence;
            $prefix = (string) config('invoice.number.prefix');

            $invoice->delete();

            DB::table('invoice_number_sequences')
                ->where('prefix', $prefix)
                ->update(['next_number' => $sequence, 'updated_at' => now()]);
        });
    }

    private function create(Client $client, ?Project $project, CarbonImmutable $from, CarbonImmutable $to): Invoice
    {
        $this->assertBillable($client);

        $projects = $this->projectsWithTime($client, $project, $from, $to);

        if ($projects->isEmpty()) {
            throw ValidationException::withMessages([
                'period' => 'There are no completed time entries in the selected period.',
            ]);
        }

        $rate = (float) $client->hourly_rate;
        $issueDate = CarbonImmutable::now(config('app.display_timezone'))->startOfDay();
        $terms = (int) config('invoice.payment_terms_days');

        return DB::transaction(function () use ($client, $projects, $from, $to, $rate, $issueDate, $terms) {
            $allocated = $this->numberService->allocate();

            $invoice = Invoice::create([
                'client_id' => $client->id,
                'number' => $allocated['number'],
                'number_sequence' => $allocated['sequence'],
                'status' => InvoiceStatus::Draft,
                'issue_date' => $issueDate->toDateString(),
                'payment_terms_days' => $terms,
                'due_date' => $issueDate->addDays($terms)->toDateString(),
                'currency' => $client->currency?->value ?? 'EUR',
                'period_start' => $from->toDateString(),
                'period_end' => $to->toDateString(),
                'total_amount' => 0,
                'recipient_name' => $client->name,
                'recipient_contact_person' => $client->contact_person,
                'recipient_street' => $client->street,
                'recipient_postal_code' => $client->postal_code,
                'recipient_city' => $client->city,
                'recipient_country' => $client->country,
                'recipient_vat_id' => $client->vat_id,
            ]);

            $sortOrder = 0;

            foreach ($projects as $billable) {
                // Rounded first, so quantity times price matches the printed
                // amount.
                $quantity = round($billable['seconds'] / 3600, 2);

                $invoice->items()->create([
                    'project_id' => $billable['project']->id,
                    'description' => $billable['project']->title,
                    'service_date' => $issueDate->toDateString(),
                    'quantity' => $quantity,
                    'unit' => config('invoice.default_unit'),
                    'unit_price' => $rate,
                    'amount' => round($quantity * $rate, 2),
                    'source_seconds' => $billable['seconds'],
                    'sort_order' => $sortOrder++,
                ]);
            }

            $this->recalculateTotal($invoice);

            return $invoice->fresh('items');
        });
    }

    /**
     * Boundaries arrive in the display timezone and go to UTC for the query, or
     * entries near a month boundary land in the wrong month.
     *
     * @return \Illuminate\Support\Collection<int, array{project: Project, seconds: int}>
     */
    private function projectsWithTime(Client $client, ?Project $only, CarbonImmutable $from, CarbonImmutable $to): \Illuminate\Support\Collection
    {
        return $client->projects()
            ->when($only, fn ($q) => $q->whereKey($only->id))
            ->withSum([
                'timeLogs as billable_seconds' => fn ($q) => $q
                    ->completed()
                    ->whereBetween('started_at', [$from->utc(), $to->utc()]),
            ], 'duration_seconds')
            ->orderBy('title')
            ->get()
            ->map(fn (Project $project) => [
                'project' => $project,
                'seconds' => (int) ($project->billable_seconds ?? 0),
            ])
            ->filter(fn (array $row) => $row['seconds'] > 0)
            ->values();
    }

    private function assertBillable(Client $client): void
    {
        if ($client->hourly_rate === null) {
            throw ValidationException::withMessages([
                'client_id' => "No hourly rate is set for {$client->name}.",
            ]);
        }

        $missing = collect([
            'street' => 'street',
            'postal_code' => 'postal code',
            'city' => 'city',
        ])->reject(fn ($label, $field) => filled($client->{$field}))->values();

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'client_id' => "The address for {$client->name} is incomplete, missing: "
                    .$missing->implode(', ').'. It is required by § 34a UStDV.',
            ]);
        }
    }

    private function transition(Invoice $invoice, InvoiceStatus $target, string $timestampColumn): Invoice
    {
        if (! $invoice->status->canTransitionTo($target)) {
            throw new InvoiceStateException(
                "Cannot move invoice {$invoice->number} from {$invoice->status->value} to {$target->value}."
            );
        }

        $invoice->update([
            'status' => $target,
            $timestampColumn => now(),
        ]);

        return $invoice;
    }

    private function assertEditable(Invoice $invoice): void
    {
        if (! $invoice->status->isEditable()) {
            throw new InvoiceStateException(
                "Invoice {$invoice->number} is {$invoice->status->value} and can no longer be changed."
            );
        }
    }

    private function recalculateTotal(Invoice $invoice): void
    {
        $invoice->update([
            'total_amount' => round((float) $invoice->items()->sum('amount'), 2),
        ]);
    }
}
