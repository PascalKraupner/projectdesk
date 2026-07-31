<?php

namespace App\Http\Resources;

use App\Models\TimeLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TimeLog */
class TimeEntryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'state' => $this->state->value,
            'started_at' => $this->started_at?->toIso8601String(),
            'last_resumed_at' => $this->last_resumed_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'duration_seconds' => (int) ($this->duration_seconds ?? 0),
            'note' => $this->note,
            'project' => ProjectResource::make($this->whenLoaded('project')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
