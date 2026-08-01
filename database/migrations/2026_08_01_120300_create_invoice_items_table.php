<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();

            $table->string('description');
            $table->date('service_date');
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 16);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('amount', 10, 2);

            // Traceability only. The amount comes from the rounded quantity so
            // the printed figures multiply out.
            $table->unsignedInteger('source_seconds')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['invoice_id', 'sort_order']);
        });
    }
};
