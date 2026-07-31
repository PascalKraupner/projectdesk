<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Exceptions\TimerConflictException;
use App\Models\Project;
use App\Models\TimeLog;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TimeLogService
{
    /**
     * @param  array{project_id?: int|null, client_id?: int|null, from?: string|null, to?: string|null}  $filters
     * @return LengthAwarePaginator<int, TimeLog>
     */
    public function paginate(int $perPage, array $filters = []): LengthAwarePaginator
    {
        return TimeLog::query()
            ->when($filters['project_id'] ?? null, fn ($q, $id) => $q->where('project_id', $id))
            ->when($filters['client_id'] ?? null, fn ($q, $id) => $q
                ->whereHas('project', fn ($p) => $p->where('client_id', $id)))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('started_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('started_at', '<=', $to))
            ->latest('started_at')
            ->paginate($perPage);
    }

    public function current(): ?TimeLog
    {
        return TimeLog::active()->latest('started_at')->first();
    }

    public function start(Project $project, ?string $note = null): TimeLog
    {
        if ($project->status !== ProjectStatus::Active) {
            throw new TimerConflictException('Cannot start a timer on a non-active project.');
        }

        if (TimeLog::active()->exists()) {
            throw new TimerConflictException('A timer is already in progress.');
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
            throw new TimerConflictException('Cannot pause a stopped timer.');
        }

        if ($log->last_resumed_at === null) {
            throw new TimerConflictException('This timer is already paused.');
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
            throw new TimerConflictException('This timer is already running.');
        }

        if (TimeLog::active()->whereKeyNot($log->getKey())->exists()) {
            throw new TimerConflictException('Another timer is already in progress.');
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
            throw new TimerConflictException('This timer has already been stopped.');
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

    public function createManualForProject(int $projectId, string $startedAt, int $durationSeconds, ?string $note = null): TimeLog
    {
        return $this->createManual(Project::findOrFail($projectId), $startedAt, $durationSeconds, $note);
    }

    public function createManual(Project $project, string $startedAt, int $durationSeconds, ?string $note = null): TimeLog
    {
        // Normalise to UTC: Eloquent formats a Carbon in its own timezone, so an
        // offset-bearing input would otherwise be stored as the wrong instant.
        $start = CarbonImmutable::parse($startedAt)->utc();

        return $project->timeLogs()->create([
            'started_at' => $start,
            'ended_at' => $start->addSeconds($durationSeconds),
            'duration_seconds' => $durationSeconds,
            'note' => $note,
        ]);
    }

    public function updateManual(TimeLog $log, string $startedAt, int $durationSeconds, ?string $note): TimeLog
    {
        if ($log->ended_at === null) {
            throw new TimerConflictException('Cannot edit a running timer.');
        }

        $start = CarbonImmutable::parse($startedAt)->utc();

        $log->update([
            'started_at' => $start,
            'ended_at' => $start->addSeconds($durationSeconds),
            'duration_seconds' => $durationSeconds,
            'note' => $note,
        ]);

        return $log;
    }

    /** @param  array<string, mixed>  $data */
    public function updateManualPartial(TimeLog $log, array $data): TimeLog
    {
        return $this->updateManual(
            $log,
            $data['started_at'] ?? $log->started_at->toIso8601String(),
            (int) ($data['duration_seconds'] ?? $log->duration_seconds),
            array_key_exists('note', $data) ? $data['note'] : $log->note,
        );
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
