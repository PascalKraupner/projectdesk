<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TimeLog;
use Carbon\CarbonImmutable;
use RuntimeException;

class TimeLogService
{
    public function start(Project $project, ?string $note = null): TimeLog
    {
        if (TimeLog::running()->exists()) {
            throw new RuntimeException('A timer is already running.');
        }

        return $project->timeLogs()->create([
            'started_at' => now(),
            'note' => $note,
        ]);
    }

    public function stop(TimeLog $log): TimeLog
    {
        if ($log->ended_at !== null) {
            throw new RuntimeException('This timer has already been stopped.');
        }

        $endedAt = now();

        $log->update([
            'ended_at' => $endedAt,
            'duration_seconds' => $log->started_at->diffInSeconds($endedAt),
        ]);

        return $log;
    }

    public function createManual(Project $project, string $startedAt, string $endedAt, ?string $note = null): TimeLog
    {
        $start = CarbonImmutable::parse($startedAt);
        $end = CarbonImmutable::parse($endedAt);

        return $project->timeLogs()->create([
            'started_at' => $start,
            'ended_at' => $end,
            'duration_seconds' => $start->diffInSeconds($end),
            'note' => $note,
        ]);
    }

    public function updateManual(TimeLog $log, string $startedAt, string $endedAt, ?string $note): TimeLog
    {
        if ($log->ended_at === null) {
            throw new RuntimeException('Cannot edit a running timer.');
        }

        $start = CarbonImmutable::parse($startedAt);
        $end = CarbonImmutable::parse($endedAt);

        $log->update([
            'started_at' => $start,
            'ended_at' => $end,
            'duration_seconds' => $start->diffInSeconds($end),
            'note' => $note,
        ]);

        return $log;
    }

    public function updateNote(TimeLog $log, ?string $note): TimeLog
    {
        $log->update(['note' => $note]);

        return $log;
    }

    public function delete(TimeLog $log): void
    {
        $log->delete();
    }
}
