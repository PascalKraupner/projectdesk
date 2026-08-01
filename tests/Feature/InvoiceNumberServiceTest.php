<?php

namespace Tests\Feature;

use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class InvoiceNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): InvoiceNumberService
    {
        return app(InvoiceNumberService::class);
    }

    public function test_numbers_start_at_one_and_run_consecutively(): void
    {
        $this->assertSame('R0000001', $this->service()->allocate()['number']);
        $this->assertSame('R0000002', $this->service()->allocate()['number']);
        $this->assertSame('R0000003', $this->service()->allocate()['number']);
    }

    public function test_the_sequence_is_returned_alongside_the_number(): void
    {
        $this->service()->allocate();

        $allocated = $this->service()->allocate();

        $this->assertSame(2, $allocated['sequence']);
        $this->assertSame('R0000002', $allocated['number']);
    }

    public function test_prefix_and_length_come_from_config(): void
    {
        config(['invoice.number.prefix' => 'INV-', 'invoice.number.length' => 4]);

        $this->assertSame('INV-0001', $this->service()->allocate()['number']);
        $this->assertSame('INV-0002', $this->service()->allocate()['number']);
    }

    public function test_a_longer_sequence_is_not_truncated_by_the_padding(): void
    {
        config(['invoice.number.length' => 3]);

        DB::table('invoice_number_sequences')->insert([
            'prefix' => 'R', 'next_number' => 12345,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame('R12345', $this->service()->allocate()['number']);
    }

    public function test_a_failed_transaction_does_not_burn_a_number(): void
    {
        $this->assertSame('R0000001', $this->service()->allocate()['number']);

        try {
            DB::transaction(function () {
                $this->service()->allocate();
                throw new RuntimeException('creation fails after allocation');
            });
        } catch (RuntimeException) {
            // expected
        }

        // Without the counter rolling back this would be R0000003, skipping 2.
        $this->assertSame('R0000002', $this->service()->allocate()['number']);
    }

    public function test_peek_shows_the_next_number_without_consuming_it(): void
    {
        $this->assertSame('R0000001', $this->service()->peek());
        $this->assertSame('R0000001', $this->service()->peek());

        $this->service()->allocate();

        $this->assertSame('R0000002', $this->service()->peek());
    }

    public function test_prefixes_keep_separate_counters(): void
    {
        config(['invoice.number.prefix' => 'R']);
        $this->service()->allocate();
        $this->service()->allocate();

        config(['invoice.number.prefix' => 'S']);
        $this->assertSame('S0000001', $this->service()->allocate()['number']);

        config(['invoice.number.prefix' => 'R']);
        $this->assertSame('R0000003', $this->service()->allocate()['number']);
    }
}
