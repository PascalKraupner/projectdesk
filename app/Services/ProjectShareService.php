<?php

namespace App\Services;

use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ProjectShareService
{
    public function enable(Project $project, CarbonImmutable $expiresAt): Project
    {
        $project->update([
            'share_token' => $project->share_token ?? Str::random(40),
            'share_expires_at' => $expiresAt,
        ]);

        return $project;
    }

    public function regenerate(Project $project, CarbonImmutable $expiresAt): Project
    {
        $project->update([
            'share_token' => Str::random(40),
            'share_expires_at' => $expiresAt,
        ]);

        return $project;
    }

    public function revoke(Project $project): Project
    {
        $project->update([
            'share_token' => null,
            'share_expires_at' => null,
        ]);

        return $project;
    }

    public function signedUrl(Project $project): ?string
    {
        if (! $project->share_token || ! $project->share_expires_at) {
            return null;
        }

        return URL::temporarySignedRoute(
            'projects.share',
            $project->share_expires_at,
            ['project' => $project->id, 'token' => $project->share_token],
        );
    }
}
