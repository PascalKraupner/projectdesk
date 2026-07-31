<?php

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Client */
class ClientResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'hourly_rate' => $this->hourly_rate !== null ? (float) $this->hourly_rate : null,
            'currency' => $this->currency?->value,
            'projects_count' => $this->when(
                array_key_exists('projects_count', $this->resource->getAttributes()),
                fn () => (int) $this->projects_count,
            ),
            'total_seconds' => $this->when(
                array_key_exists('total_seconds', $this->resource->getAttributes()),
                fn () => (int) $this->total_seconds,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
