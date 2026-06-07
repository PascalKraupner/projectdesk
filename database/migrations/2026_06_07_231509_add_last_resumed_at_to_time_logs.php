<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            $table->dateTime('last_resumed_at')->nullable()->after('started_at');
        });

        DB::table('time_logs')
            ->whereNull('ended_at')
            ->update([
                'last_resumed_at' => DB::raw('started_at'),
                'duration_seconds' => 0,
            ]);
    }
};
