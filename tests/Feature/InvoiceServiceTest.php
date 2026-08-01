<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Exceptions\InvoiceStateException;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\TimeLog;
use App\Services\InvoiceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Europe/Berlin';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.display_timezone' => self::TZ]);
        $this->travelTo('2026-08-01 09:00:00');
    }

    private function service(): InvoiceService
    {
        return app(InvoiceService::class);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function july(): array
    {
        return [
            CarbonImmutable::parse('2026-07-01', self::TZ)->startOfDay(),
            CarbonImmutable::parse('2026-07-31', self::TZ)->endOfDay(),
        ];
    }

    private function logHours(Project $project, string $startedAt, int $seconds): TimeLog
    {
        return TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => $startedAt,
            'ended_at' => CarbonImmutable::parse($startedAt)->addSeconds($seconds),
            'duration_seconds' => $seconds,
        ]);
    }

    public function test_one_item_per_project_not_per_entry(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->billable()->create();
        $alpha = Project::factory()->create(['client_id' => $client->id, 'title' => 'Alpha']);
        $beta = Project::factory()->create(['client_id' => $client->id, 'title' => 'Beta']);

        $this->logHours($alpha, '2026-07-02 09:00:00', 3600);
        $this->logHours($alpha, '2026-07-03 09:00:00', 1800);
        $this->logHours($beta, '2026-07-04 09:00:00', 7200);

        $invoice = $this->service()->createForClient($client, $from, $to);

        $this->assertCount(2, $invoice->items);
        $this->assertSame(['Alpha', 'Beta'], $invoice->items->pluck('description')->all());
        $this->assertSame('1.50', $invoice->items->firstWhere('description', 'Alpha')->quantity);
    }

    public function test_the_amount_is_computed_from_the_rounded_quantity_so_the_invoice_adds_up(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->billable(80.00)->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        // 2579s = 0.716388h -> 0.72 rounded, times 80 is 57.60.
        $this->logHours($project, '2026-07-02 09:00:00', 2579);

        $invoice = $this->service()->createForClient($client, $from, $to);
        $item = $invoice->items->first();

        $this->assertSame('0.72', $item->quantity);
        $this->assertSame('57.60', $item->amount);
        $this->assertSame(2579, $item->source_seconds);

        // Printed quantity times printed price must equal the printed amount.
        $this->assertSame($item->amount, number_format(
            (float) $item->quantity * (float) $item->unit_price, 2, '.', ''
        ));
    }

    public function test_the_total_is_the_sum_of_the_items(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->billable(80.00)->create();
        $a = Project::factory()->create(['client_id' => $client->id, 'title' => 'A']);
        $b = Project::factory()->create(['client_id' => $client->id, 'title' => 'B']);

        $this->logHours($a, '2026-07-02 09:00:00', 15300);  // 4,25 h -> 340,00
        $this->logHours($b, '2026-07-03 09:00:00', 4968);   // 1,38 h -> 110,40

        $invoice = $this->service()->createForClient($client, $from, $to);

        $this->assertSame('450.40', $invoice->total_amount);
    }

    public function test_only_completed_entries_inside_the_period_count(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->billable()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        $this->logHours($project, '2026-07-10 09:00:00', 3600);
        $this->logHours($project, '2026-06-10 09:00:00', 7200);   // before
        $this->logHours($project, '2026-08-10 09:00:00', 7200);   // after
        TimeLog::factory()->running()->create(['project_id' => $project->id]);
        TimeLog::factory()->paused()->create(['project_id' => $project->id]);

        $invoice = $this->service()->createForClient($client, $from, $to);

        $this->assertCount(1, $invoice->items);
        $this->assertSame(3600, $invoice->items->first()->source_seconds);
    }

    public function test_period_boundaries_follow_the_display_timezone(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->billable()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        // 22:00 UTC on 31 July is already 1 August in Berlin, so it is excluded.
        $this->logHours($project, '2026-07-31 22:00:00', 3600);
        // 22:30 UTC on 30 June is already 1 July in Berlin, so it is included.
        $this->logHours($project, '2026-06-30 22:30:00', 1800);

        $invoice = $this->service()->createForClient($client, $from, $to);

        $this->assertSame(1800, $invoice->items->first()->source_seconds);
    }

    public function test_creating_for_a_single_project_ignores_the_others(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->billable()->create();
        $wanted = Project::factory()->create(['client_id' => $client->id, 'title' => 'Wanted']);
        $other = Project::factory()->create(['client_id' => $client->id, 'title' => 'Other']);

        $this->logHours($wanted, '2026-07-02 09:00:00', 3600);
        $this->logHours($other, '2026-07-02 09:00:00', 3600);

        $invoice = $this->service()->createForProject($wanted, $from, $to);

        $this->assertCount(1, $invoice->items);
        $this->assertSame('Wanted', $invoice->items->first()->description);
    }

    public function test_the_recipient_is_snapshotted_so_a_later_move_does_not_rewrite_history(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->billable()->create([
            'name' => 'MÜNCH GmbH',
            'street' => 'Würzburger Straße 7',
            'postal_code' => '97753',
            'city' => 'Karlstadt',
            'vat_id' => 'DE811933977',
        ]);
        $project = Project::factory()->create(['client_id' => $client->id]);
        $this->logHours($project, '2026-07-02 09:00:00', 3600);

        $invoice = $this->service()->createForClient($client, $from, $to);

        $client->update(['street' => 'Neue Straße 1', 'city' => 'Berlin']);

        $invoice->refresh();
        $this->assertSame('MÜNCH GmbH', $invoice->recipient_name);
        $this->assertSame('Würzburger Straße 7', $invoice->recipient_street);
        $this->assertSame('Karlstadt', $invoice->recipient_city);
        $this->assertSame('DE811933977', $invoice->recipient_vat_id);
    }

    public function test_the_invoice_survives_deleting_the_client(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->billable()->create(['name' => 'Weg GmbH']);
        $project = Project::factory()->create(['client_id' => $client->id]);
        $this->logHours($project, '2026-07-02 09:00:00', 3600);

        $invoice = $this->service()->createForClient($client, $from, $to);
        $client->delete();

        $invoice->refresh();
        $this->assertNull($invoice->client_id);
        $this->assertSame('Weg GmbH', $invoice->recipient_name);
    }

    public function test_due_date_follows_the_payment_terms(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->billable()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $this->logHours($project, '2026-07-02 09:00:00', 3600);

        $invoice = $this->service()->createForClient($client, $from, $to);

        $this->assertSame('2026-08-01', $invoice->issue_date->toDateString());
        $this->assertSame(14, $invoice->payment_terms_days);
        $this->assertSame('2026-08-15', $invoice->due_date->toDateString());
    }

    public function test_a_client_without_an_hourly_rate_cannot_be_billed(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->create(['hourly_rate' => null]);
        $project = Project::factory()->create(['client_id' => $client->id]);
        $this->logHours($project, '2026-07-02 09:00:00', 3600);

        $this->expectException(ValidationException::class);

        $this->service()->createForClient($client, $from, $to);
    }

    public function test_an_incomplete_address_names_the_missing_fields(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->create([
            'hourly_rate' => 80,
            'street' => 'Würzburger Straße 7',
            'postal_code' => null,
            'city' => null,
        ]);
        $project = Project::factory()->create(['client_id' => $client->id]);
        $this->logHours($project, '2026-07-02 09:00:00', 3600);

        try {
            $this->service()->createForClient($client, $from, $to);
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $message = $e->errors()['client_id'][0];
            $this->assertStringContainsString('postal code', $message);
            $this->assertStringContainsString('city', $message);
            $this->assertStringNotContainsString('street', $message);
        }
    }

    public function test_a_period_without_time_is_rejected(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->billable()->create();
        Project::factory()->create(['client_id' => $client->id]);

        $this->expectException(ValidationException::class);

        $this->service()->createForClient($client, $from, $to);
    }

    public function test_numbers_stay_gapless_across_invoices(): void
    {
        [$from, $to] = $this->july();
        $numbers = [];

        foreach (range(1, 3) as $i) {
            $client = Client::factory()->billable()->create();
            $project = Project::factory()->create(['client_id' => $client->id]);
            $this->logHours($project, '2026-07-02 09:00:00', 3600);
            $numbers[] = $this->service()->createForClient($client, $from, $to)->number;
        }

        $this->assertSame(['R0000001', 'R0000002', 'R0000003'], $numbers);
    }

    public function test_cancelling_does_not_reuse_or_free_the_number(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->billable()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $this->logHours($project, '2026-07-02 09:00:00', 3600);

        $first = $this->service()->createForClient($client, $from, $to);
        $this->service()->cancel($first);

        $second = $this->service()->createForClient($client, $from, $to);

        $this->assertSame('R0000001', $first->number);
        $this->assertSame('R0000002', $second->number);
        $this->assertSame(InvoiceStatus::Cancelled, $first->fresh()->status);
    }

    public function test_overlapping_invoices_are_reported_for_the_warning(): void
    {
        [$from, $to] = $this->july();
        $client = Client::factory()->billable()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $this->logHours($project, '2026-07-02 09:00:00', 3600);

        $existing = $this->service()->createForClient($client, $from, $to);

        $this->assertTrue($this->service()->overlapping($client, $from, $to)->contains($existing));

        $this->service()->cancel($existing);
        $this->assertFalse($this->service()->overlapping($client, $from, $to)->contains($existing));
    }

    public function test_a_manual_item_can_be_added_and_the_total_follows(): void
    {
        $invoice = Invoice::factory()->create();

        $this->service()->addItem($invoice, [
            'description' => 'Lizenzkosten',
            'quantity' => 1,
            'unit' => 'Stk',
            'unit_price' => 49.90,
        ]);

        $this->assertSame('49.90', $invoice->fresh()->total_amount);
        $this->assertSame('Stk', $invoice->items()->first()->unit);
    }

    public function test_an_amount_can_be_overridden_without_touching_quantity(): void
    {
        $invoice = Invoice::factory()->create();
        $item = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 10,
            'unit_price' => 80,
            'amount' => 800,
        ]);

        $this->service()->updateItem($item, ['amount' => 700]);

        $item->refresh();
        $this->assertSame('700.00', $item->amount);
        $this->assertSame('10.00', $item->quantity);
        $this->assertSame('700.00', $invoice->fresh()->total_amount);
    }

    public function test_changing_the_quantity_recomputes_the_amount(): void
    {
        $invoice = Invoice::factory()->create();
        $item = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 1,
            'unit_price' => 80,
            'amount' => 80,
        ]);

        $this->service()->updateItem($item, ['quantity' => 2.5]);

        $this->assertSame('200.00', $item->fresh()->amount);
    }

    public function test_removing_an_item_updates_the_total(): void
    {
        $invoice = Invoice::factory()->create();
        $keep = InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'amount' => 100, 'quantity' => 1, 'unit_price' => 100]);
        $drop = InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'amount' => 50, 'quantity' => 1, 'unit_price' => 50]);

        $this->service()->removeItem($drop);

        $this->assertSame('100.00', $invoice->fresh()->total_amount);
        $this->assertCount(1, $invoice->fresh()->items);
        $this->assertNotNull($keep->fresh());
    }

    public function test_an_issued_invoice_cannot_be_edited(): void
    {
        $invoice = Invoice::factory()->issued()->create();
        $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $this->expectException(InvoiceStateException::class);

        $this->service()->updateItem($item, ['amount' => 1]);
    }

    public function test_an_issued_invoice_cannot_gain_items(): void
    {
        $invoice = Invoice::factory()->issued()->create();

        $this->expectException(InvoiceStateException::class);

        $this->service()->addItem($invoice, [
            'description' => 'Nachtrag', 'quantity' => 1, 'unit_price' => 10,
        ]);
    }

    public function test_the_status_path_draft_issued_paid(): void
    {
        $invoice = Invoice::factory()->create();
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $this->service()->issue($invoice);
        $this->assertSame(InvoiceStatus::Issued, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->issued_at);

        $this->service()->markPaid($invoice);
        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    public function test_an_empty_invoice_cannot_be_issued(): void
    {
        $invoice = Invoice::factory()->create();

        $this->expectException(InvoiceStateException::class);

        $this->service()->issue($invoice);
    }

    public function test_a_draft_cannot_jump_straight_to_paid(): void
    {
        $invoice = Invoice::factory()->create();
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $this->expectException(InvoiceStateException::class);

        $this->service()->markPaid($invoice);
    }

    public function test_a_cancelled_invoice_is_a_dead_end(): void
    {
        $invoice = Invoice::factory()->cancelled()->create();

        $this->expectException(InvoiceStateException::class);

        $this->service()->issue($invoice);
    }

    public function test_a_paid_invoice_can_still_be_cancelled(): void
    {
        $invoice = Invoice::factory()->paid()->create();

        $this->service()->cancel($invoice);

        $this->assertSame(InvoiceStatus::Cancelled, $invoice->fresh()->status);
    }

    public function test_updating_the_invoice_never_changes_its_number(): void
    {
        $invoice = Invoice::factory()->create(['number' => 'R0000042']);

        $this->service()->updateInvoice($invoice, [
            'issue_date' => '2026-09-01',
            'payment_terms_days' => 30,
        ]);

        $invoice->refresh();
        $this->assertSame('R0000042', $invoice->number);
        $this->assertSame('2026-10-01', $invoice->due_date->toDateString());
    }
}
