<?php

namespace App\Models;

use App\Enums\TimeEntryState;
use Database\Factories\TimeLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeLog extends Model
{
    /** @use HasFactory<TimeLogFactory> */
    use HasFactory;

    protected $fillable = ['project_id', 'started_at', 'last_resumed_at', 'ended_at', 'duration_seconds', 'note'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_resumed_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected function state(): Attribute
    {
        return Attribute::get(fn (): TimeEntryState => match (true) {
            $this->ended_at !== null => TimeEntryState::Completed,
            $this->last_resumed_at !== null => TimeEntryState::Running,
            default => TimeEntryState::Paused,
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    public function scopeTicking(Builder $query): Builder
    {
        return $query->whereNotNull('last_resumed_at')->whereNull('ended_at');
    }

    public function scopePaused(Builder $query): Builder
    {
        return $query->whereNull('last_resumed_at')->whereNull('ended_at');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('ended_at');
    }
}
