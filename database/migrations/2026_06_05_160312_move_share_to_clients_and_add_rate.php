<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('share_token', 64)->nullable()->unique();
            $table->dateTime('share_expires_at')->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['share_token']);
            $table->dropColumn(['share_token', 'share_expires_at']);
        });
    }
};
