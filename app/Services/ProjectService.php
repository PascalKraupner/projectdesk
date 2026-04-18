<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class ProjectService
{
    public function all(): Collection
    {
        return Project::with('client')->latest()->get();
    }

    public function find(int $id): Project
    {
        return Project::with('client')->findOrFail($id);
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
