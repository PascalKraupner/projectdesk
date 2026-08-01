<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    /**
     * Must run inside the transaction that writes the invoice, so a failed
     * creation rolls the counter back and leaves no gap.
     *
     * @return array{number: string, sequence: int}
     */
    public function allocate(): array
    {
        $prefix = (string) config('invoice.number.prefix');

        DB::table('invoice_number_sequences')->insertOrIgnore([
            'prefix' => $prefix,
            'next_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::transaction(function () use ($prefix) {
            $sequence = (int) DB::table('invoice_number_sequences')
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->value('next_number');

            DB::table('invoice_number_sequences')
                ->where('prefix', $prefix)
                ->update(['next_number' => $sequence + 1, 'updated_at' => now()]);

            return [
                'number' => $this->format($sequence, $prefix),
                'sequence' => $sequence,
            ];
        });
    }

    public function format(int $sequence, ?string $prefix = null): string
    {
        $prefix ??= (string) config('invoice.number.prefix');
        $length = (int) config('invoice.number.length');

        return $prefix.str_pad((string) $sequence, $length, '0', STR_PAD_LEFT);
    }

    public function peek(): string
    {
        $prefix = (string) config('invoice.number.prefix');

        $next = DB::table('invoice_number_sequences')
            ->where('prefix', $prefix)
            ->value('next_number');

        return $this->format((int) ($next ?? 1), $prefix);
    }
}
