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
}
