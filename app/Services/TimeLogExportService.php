<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TimeLogExportService
{
    public function streamProjectCsv(Project $project): StreamedResponse
    {
        $filename = Str::slug($project->title).'-time-logs.csv';

        return response()->streamDownload(function () use ($project) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Started', 'Ended', 'Duration', 'Seconds', 'Note']);

            $project->timeLogs()
                ->completed()
                ->oldest('started_at')
                ->lazy()
                ->each(function ($log) use ($out) {
                    fputcsv($out, [
                        $log->started_at->toIso8601String(),
                        $log->ended_at->toIso8601String(),
                        gmdate('H:i:s', $log->duration_seconds),
                        $log->duration_seconds,
                        $log->note,
                    ]);
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
