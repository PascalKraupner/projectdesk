<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProjectService
{
    public function all(): Collection
    {
        return Project::with('client')
            ->withSum('timeLogs as total_seconds', 'duration_seconds')
            ->latest()
            ->get();
    }

    /** @return LengthAwarePaginator<int, Project> */
    public function paginate(int $perPage, ?int $clientId = null): LengthAwarePaginator
    {
        return Project::withSum('timeLogs as total_seconds', 'duration_seconds')
            ->when($clientId, fn ($q, $id) => $q->where('client_id', $id))
            ->latest()
            ->paginate($perPage);
    }

    public function findWithTotals(int $id): Project
    {
        return Project::withSum('timeLogs as total_seconds', 'duration_seconds')
            ->findOrFail($id);
    }

    public function find(int $id): Project
    {
        return Project::with([
            'client',
            'timeLogs' => fn ($q) => $q->latest('started_at'),
        ])
            ->withSum('timeLogs as total_seconds', 'duration_seconds')
            ->findOrFail($id);
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Project
    {
        return Project::create($data);
    }

    /** @param  array<string, mixed>  $data */
    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project;
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
