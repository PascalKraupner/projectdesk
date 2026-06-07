<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\TimeLog;
use Carbon\CarbonImmutable;
use RuntimeException;

class TimeLogService
{
    public function start(Project $project, ?string $note = null): TimeLog
    {
        if ($project->status !== ProjectStatus::Active) {
            throw new RuntimeException('Cannot start a timer on a non-active project.');
        }

        if (TimeLog::active()->exists()) {
            throw new RuntimeException('A timer is already in progress.');
        }

        $now = now();

        return $project->timeLogs()->create([
            'started_at' => $now,
            'last_resumed_at' => $now,
            'duration_seconds' => 0,
            'note' => $note,
        ]);
    }

    public function pause(TimeLog $log): TimeLog
    {
        if ($log->ended_at !== null) {
            throw new RuntimeException('Cannot pause a stopped timer.');
        }

        if ($log->last_resumed_at === null) {
            throw new RuntimeException('This timer is already paused.');
        }

        $log->update([
            'duration_seconds' => ($log->duration_seconds ?? 0)
                + $log->last_resumed_at->diffInSeconds(now()),
            'last_resumed_at' => null,
        ]);

        return $log;
    }

    public function resume(TimeLog $log): TimeLog
    {
        if ($log->last_resumed_at !== null) {
            throw new RuntimeException('This timer is already running.');
        }

        if (TimeLog::active()->whereKeyNot($log->getKey())->exists()) {
            throw new RuntimeException('Another timer is already in progress.');
        }

        $log->update([
            'last_resumed_at' => now(),
            'ended_at' => null,
        ]);

        return $log;
    }

    public function stop(TimeLog $log): TimeLog
    {
        if ($log->ended_at !== null) {
            throw new RuntimeException('This timer has already been stopped.');
        }

        $now = now();
        $accumulated = $log->duration_seconds ?? 0;

        if ($log->last_resumed_at !== null) {
            $accumulated += $log->last_resumed_at->diffInSeconds($now);
        }

        $log->update([
            'ended_at' => $now,
            'last_resumed_at' => null,
            'duration_seconds' => $accumulated,
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
