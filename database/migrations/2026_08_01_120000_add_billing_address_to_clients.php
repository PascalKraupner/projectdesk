<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable so existing clients keep working; invoice creation checks
        // completeness itself.
        Schema::table('clients', function (Blueprint $table) {
            $table->string('contact_person')->nullable()->after('email');
            $table->string('street')->nullable()->after('contact_person');
            $table->string('postal_code', 32)->nullable()->after('street');
            $table->string('city')->nullable()->after('postal_code');
            $table->string('country')->nullable()->after('city');
            $table->string('vat_id', 32)->nullable()->after('country');
        });
    }
};
