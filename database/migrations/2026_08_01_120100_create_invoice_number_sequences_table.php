<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per prefix, locked during allocation to keep the sequence
        // gapless. MAX(number)+1 would not guarantee that.
        Schema::create('invoice_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 16)->unique();
            $table->unsignedInteger('next_number')->default(1);
            $table->timestamps();
        });
    }
};
