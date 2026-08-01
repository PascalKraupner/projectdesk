<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\InvoiceNumberService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.display_timezone' => 'Europe/Berlin']);
        $this->travelTo('2026-08-01 09:00:00');
    }

    private function user(): User
    {
        return User::factory()->create();
    }

    private function billableClientWithTime(int $seconds = 3600): Client
    {
        $client = Client::factory()->billable(80.00)->create();
        $project = Project::factory()->create(['client_id' => $client->id, 'title' => 'Website']);

        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-07-10 09:00:00',
            'ended_at' => CarbonImmutable::parse('2026-07-10 09:00:00')->addSeconds($seconds),
            'duration_seconds' => $seconds,
        ]);

        return $client;
    }

    public function test_every_invoice_route_requires_a_login(): void
    {
        $invoice = Invoice::factory()->create();

        $this->get('/invoices')->assertRedirect('/login');
        $this->get("/invoices/{$invoice->id}")->assertRedirect('/login');
        $this->post('/invoices', [])->assertRedirect('/login');
        $this->get("/invoices/{$invoice->id}/download.pdf")->assertRedirect('/login');
        $this->patch("/invoices/{$invoice->id}/issue")->assertRedirect('/login');
    }

    public function test_index_lists_invoices_newest_number_first(): void
    {
        Invoice::factory()->create(['number' => 'R0000001', 'number_sequence' => 1]);
        Invoice::factory()->create(['number' => 'R0000002', 'number_sequence' => 2]);

        $this->actingAs($this->user())->get('/invoices')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Invoice/Index')
                ->has('invoices', 2)
                ->where('invoices.0.number', 'R0000002'));
    }

    public function test_index_can_filter_by_status(): void
    {
        Invoice::factory()->create();
        Invoice::factory()->paid()->create();

        $this->actingAs($this->user())->get('/invoices?status=paid')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('invoices', 1)
                ->where('invoices.0.status', 'paid')
                ->where('filter', 'paid'));
    }

    public function test_store_creates_a_draft_from_a_clients_time(): void
    {
        $client = $this->billableClientWithTime(5400);

        $response = $this->actingAs($this->user())->post('/invoices', [
            'client_id' => $client->id,
            'from' => '2026-07-01',
            'to' => '2026-07-31',
        ]);

        $invoice = Invoice::firstOrFail();
        $response->assertRedirect("/invoices/{$invoice->id}");

        $this->assertSame('R0000001', $invoice->number);
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertSame('120.00', $invoice->total_amount);
        $this->assertSame('Website', $invoice->items->first()->description);
    }

    public function test_store_accepts_a_project_instead_of_a_client(): void
    {
        $client = $this->billableClientWithTime();
        $project = $client->projects()->firstOrFail();

        $this->actingAs($this->user())->post('/invoices', [
            'project_id' => $project->id,
            'from' => '2026-07-01',
            'to' => '2026-07-31',
        ])->assertSessionHasNoErrors();

        $this->assertSame($project->id, Invoice::firstOrFail()->items->first()->project_id);
    }

    public function test_store_needs_either_a_client_or_a_project(): void
    {
        $this->actingAs($this->user())
            ->post('/invoices', ['from' => '2026-07-01'])
            ->assertSessionHasErrors('client_id');
    }

    public function test_store_defaults_the_period_to_the_current_month(): void
    {
        $client = Client::factory()->billable()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-08-05 09:00:00',
            'ended_at' => '2026-08-05 10:00:00',
            'duration_seconds' => 3600,
        ]);

        $this->actingAs($this->user())->post('/invoices', ['client_id' => $client->id])
            ->assertSessionHasNoErrors();

        $invoice = Invoice::firstOrFail();
        $this->assertSame('2026-08-01', $invoice->period_start->toDateString());
        $this->assertSame('2026-08-31', $invoice->period_end->toDateString());
    }

    public function test_store_reports_an_incomplete_address_as_a_validation_error(): void
    {
        $client = Client::factory()->create(['hourly_rate' => 80, 'city' => null]);
        $project = Project::factory()->create(['client_id' => $client->id]);
        TimeLog::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-07-10 09:00:00',
            'ended_at' => '2026-07-10 10:00:00',
            'duration_seconds' => 3600,
        ]);

        $this->actingAs($this->user())
            ->post('/invoices', ['client_id' => $client->id, 'from' => '2026-07-01', 'to' => '2026-07-31'])
            ->assertSessionHasErrors('client_id');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_store_warns_when_the_period_is_already_covered(): void
    {
        $client = $this->billableClientWithTime();
        $payload = ['client_id' => $client->id, 'from' => '2026-07-01', 'to' => '2026-07-31'];

        $this->actingAs($this->user())->post('/invoices', $payload);
        $this->actingAs($this->user())->post('/invoices', $payload)
            ->assertSessionHas('warning', fn ($w) => str_contains((string) $w, 'R0000001'));
    }

    public function test_show_exposes_items_and_the_recipient_snapshot(): void
    {
        $invoice = Invoice::factory()->create(['recipient_name' => 'MÜNCH GmbH']);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'description' => 'Projektmanagement']);

        $this->actingAs($this->user())->get("/invoices/{$invoice->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Invoice/Show')
                ->where('invoice.recipient.name', 'MÜNCH GmbH')
                ->where('invoice.editable', true)
                ->has('invoice.items', 1)
                ->where('invoice.items.0.description', 'Projektmanagement'));
    }

    public function test_a_draft_item_can_be_added_updated_and_removed(): void
    {
        $invoice = Invoice::factory()->create();

        $this->actingAs($this->user())->post("/invoices/{$invoice->id}/items", [
            'description' => 'Lizenz',
            'quantity' => 2,
            'unit_price' => 25,
        ])->assertSessionHasNoErrors();

        $item = $invoice->items()->firstOrFail();
        $this->assertSame('50.00', $invoice->fresh()->total_amount);

        $this->actingAs($this->user())
            ->patch("/invoice-items/{$item->id}", ['amount' => 40])
            ->assertSessionHasNoErrors();
        $this->assertSame('40.00', $invoice->fresh()->total_amount);

        $this->actingAs($this->user())->delete("/invoice-items/{$item->id}");
        $this->assertSame('0.00', $invoice->fresh()->total_amount);
    }

    public function test_the_status_actions_walk_the_invoice_through_its_life(): void
    {
        $invoice = Invoice::factory()->create();
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);
        $user = $this->user();

        $this->actingAs($user)->patch("/invoices/{$invoice->id}/issue")->assertSessionHasNoErrors();
        $this->assertSame(InvoiceStatus::Issued, $invoice->fresh()->status);

        $this->actingAs($user)->patch("/invoices/{$invoice->id}/pay")->assertSessionHasNoErrors();
        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);

        $this->actingAs($user)->patch("/invoices/{$invoice->id}/cancel")->assertSessionHasNoErrors();
        $this->assertSame(InvoiceStatus::Cancelled, $invoice->fresh()->status);
    }

    public function test_an_issued_invoice_rejects_item_changes(): void
    {
        $invoice = Invoice::factory()->issued()->create();
        $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $this->actingAs($this->user())
            ->patchJson("/invoice-items/{$item->id}", ['amount' => 1])
            ->assertStatus(409);
    }

    public function test_an_illegal_status_jump_is_a_conflict(): void
    {
        $invoice = Invoice::factory()->create();
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $this->actingAs($this->user())
            ->patchJson("/invoices/{$invoice->id}/pay")
            ->assertStatus(409);
    }

    public function test_the_pdf_downloads_in_every_status_including_draft(): void
    {
        $user = $this->user();

        foreach (['draft' => null, 'issued' => 'issued', 'paid' => 'paid', 'cancelled' => 'cancelled'] as $label => $state) {
            $factory = Invoice::factory();
            $invoice = ($state ? $factory->{$state}() : $factory)->create();
            InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

            $response = $this->actingAs($user)->get("/invoices/{$invoice->id}/download.pdf");

            $response->assertOk();
            $this->assertStringStartsWith('%PDF-', $response->getContent(), "status {$label}");
            $this->assertStringContainsString('%%EOF', $response->getContent(), "status {$label}");
        }
    }

    public function test_a_draft_pdf_shows_its_items_and_total(): void
    {
        $client = $this->billableClientWithTime(5400);

        $this->actingAs($this->user())->post('/invoices', [
            'client_id' => $client->id,
            'from' => '2026-07-01',
            'to' => '2026-07-31',
        ]);

        $invoice = Invoice::firstOrFail();
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);

        $content = $this->actingAs($this->user())
            ->get("/invoices/{$invoice->id}/download.pdf")
            ->getContent();

        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertGreaterThan(20000, strlen($content));
        $this->assertSame('120.00', $invoice->total_amount);
    }

    public function test_the_pdf_downloads_under_the_expected_filename(): void
    {
        $invoice = Invoice::factory()->create(['number' => 'R0000003']);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->actingAs($this->user())->get("/invoices/{$invoice->id}/download.pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'INVOICE R0000003 - Pascal Kraupner.pdf',
            $response->headers->get('content-disposition'),
        );
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('%%EOF', $response->getContent());
    }

    private function draftFromTime(Client $client): Invoice
    {
        $this->actingAs($this->user())->post('/invoices', [
            'client_id' => $client->id,
            'from' => '2026-07-01',
            'to' => '2026-07-31',
        ])->assertSessionHasNoErrors();

        return Invoice::orderByDesc('number_sequence')->firstOrFail();
    }

    public function test_the_newest_draft_can_be_discarded_and_gives_its_number_back(): void
    {
        $invoice = $this->draftFromTime($this->billableClientWithTime());
        $this->assertSame('R0000001', $invoice->number);

        $this->actingAs($this->user())
            ->delete("/invoices/{$invoice->id}")
            ->assertRedirect('/invoices');

        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertSame('R0000001', app(InvoiceNumberService::class)->peek());
    }

    public function test_a_draft_that_is_not_the_newest_cannot_be_discarded(): void
    {
        $first = $this->draftFromTime($this->billableClientWithTime());
        $second = $this->draftFromTime($this->billableClientWithTime());

        $this->assertSame(['R0000001', 'R0000002'], [$first->number, $second->number]);

        $this->actingAs($this->user())
            ->deleteJson("/invoices/{$first->id}")
            ->assertStatus(409);

        $this->assertNotNull($first->fresh());
    }

    public function test_an_issued_invoice_cannot_be_discarded(): void
    {
        $invoice = $this->draftFromTime($this->billableClientWithTime());
        $this->actingAs($this->user())->patch("/invoices/{$invoice->id}/issue");

        $this->actingAs($this->user())
            ->deleteJson("/invoices/{$invoice->id}")
            ->assertStatus(409);

        $this->assertNotNull($invoice->fresh());
    }

    public function test_show_flags_whether_the_invoice_can_be_discarded(): void
    {
        $invoice = $this->draftFromTime($this->billableClientWithTime());

        $this->actingAs($this->user())->get("/invoices/{$invoice->id}")
            ->assertInertia(fn ($page) => $page->where('invoice.discardable', true));

        $this->actingAs($this->user())->patch("/invoices/{$invoice->id}/issue");

        $this->actingAs($this->user())->get("/invoices/{$invoice->id}")
            ->assertInertia(fn ($page) => $page->where('invoice.discardable', false));
    }

    public function test_discarding_then_creating_again_reuses_the_number(): void
    {
        $client = $this->billableClientWithTime();
        $first = $this->draftFromTime($client);

        $this->actingAs($this->user())->delete("/invoices/{$first->id}");

        $second = $this->draftFromTime($client);

        $this->assertSame('R0000001', $second->number);
    }
}
