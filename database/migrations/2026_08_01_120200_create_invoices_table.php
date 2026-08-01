<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // Not cascade: an issued invoice must survive its client being
            // deleted, which the recipient snapshot below makes possible.
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();

            $table->string('number', 32)->unique();
            $table->unsignedInteger('number_sequence');
            $table->string('status')->default('draft');

            $table->date('issue_date');
            $table->unsignedSmallInteger('payment_terms_days');
            $table->date('due_date');
            $table->string('currency', 3);

            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            $table->decimal('total_amount', 10, 2)->default(0);

            // Frozen at creation: a client moving must not rewrite old invoices.
            $table->string('recipient_name');
            $table->string('recipient_contact_person')->nullable();
            $table->string('recipient_street')->nullable();
            $table->string('recipient_postal_code', 32)->nullable();
            $table->string('recipient_city')->nullable();
            $table->string('recipient_country')->nullable();
            $table->string('recipient_vat_id', 32)->nullable();

            $table->dateTime('issued_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'issue_date']);
            $table->index(['client_id', 'period_start', 'period_end']);
        });
    }
};
