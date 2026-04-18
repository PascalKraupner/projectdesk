<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TimeLog;
use RuntimeException;

class TimeLogService
{
    public function start(Project $project): TimeLog
    {
        if (TimeLog::running()->exists()) {
            throw new RuntimeException('A timer is already running.');
        }

        return $project->timeLogs()->create([
            'started_at' => now(),
        ]);
    }

    public function stop(TimeLog $log, ?string $note = null): TimeLog
    {
        if ($log->ended_at !== null) {
            throw new RuntimeException('This timer has already been stopped.');
        }

        $endedAt = now();

        $log->update([
            'ended_at' => $endedAt,
            'duration_seconds' => $log->started_at->diffInSeconds($endedAt),
            'note' => $note,
        ]);

        return $log;
    }

    public function delete(TimeLog $log): void
    {
        $log->delete();
    }
}
