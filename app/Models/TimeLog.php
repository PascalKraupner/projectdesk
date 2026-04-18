<?php

namespace App\Models;

use Database\Factories\TimeLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeLog extends Model
{
    /** @use HasFactory<TimeLogFactory> */
    use HasFactory;

    protected $fillable = ['project_id', 'started_at', 'ended_at', 'duration_seconds', 'note'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('ended_at');
    }
}
