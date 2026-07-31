<?php

namespace App\Services;

use App\Enums\Currency;
use App\Models\Client;
use App\Models\Project;
use App\Models\TimeLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TimesheetService
{
    /** @return array<string, mixed> */
    public function clientDocument(Client $client, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $projects = $client->projects()
            ->with(['timeLogs' => fn ($q) => $q
                ->completed()
                ->whereBetween('started_at', [$from->utc(), $to->utc()])
                ->oldest('started_at')])
            ->orderBy('title')
            ->get()
            ->reject(fn (Project $project) => $project->timeLogs->isEmpty())
            ->values();

        $groups = $projects->map(fn (Project $project) => [
            'title' => $project->title,
            'seconds' => (int) $project->timeLogs->sum('duration_seconds'),
            'logs' => $project->timeLogs,
        ])->all();

        return $this->document($client->name, null, $client, $groups, $from, $to);
    }

    /** @return array<string, mixed> */
    public function projectDocument(Project $project, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $project->loadMissing('client');

        $logs = $project->timeLogs()
            ->completed()
            ->whereBetween('started_at', [$from, $to])
            ->oldest('started_at')
            ->get();

        $groups = $logs->isEmpty() ? [] : [[
            'title' => null,
            'seconds' => (int) $logs->sum('duration_seconds'),
            'logs' => $logs,
        ]];

        return $this->document($project->title, $project->client?->name, $project->client, $groups, $from, $to);
    }

    public function clientPdf(Client $client, CarbonImmutable $from, CarbonImmutable $to): Response
    {
        return $this->pdf($this->clientDocument($client, $from, $to));
    }

    public function projectPdf(Project $project, CarbonImmutable $from, CarbonImmutable $to): Response
    {
        return $this->pdf($this->projectDocument($project, $from, $to));
    }

    /** @param  array<string, mixed>  $document */
    private function pdf(array $document): Response
    {
        return Pdf::loadView('pdf.timesheet', $document)
            ->setPaper('a4')
            ->download($document['filename']);
    }

    /**
     * @param  array<int, array{title: ?string, seconds: int, logs: Collection<int, TimeLog>}>  $groups
     * @return array<string, mixed>
     */
    private function document(
        string $title,
        ?string $subtitle,
        ?Client $client,
        array $groups,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): array {
        $rate = $client?->hourly_rate !== null ? (float) $client->hourly_rate : null;
        $currency = ($client?->currency ?? Currency::EUR)->value;
        $totalSeconds = array_sum(array_column($groups, 'seconds'));
        $timezone = config('app.display_timezone');

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'filename' => $this->filename($title, $from, $to),
            'period' => $from->format('Y-m-d').' – '.$to->format('Y-m-d'),
            'generated_at' => CarbonImmutable::now($timezone)->format('Y-m-d H:i'),
            'rate' => $rate !== null ? Number::currency($rate, $currency) : null,
            'total_seconds' => $totalSeconds,
            'total_duration' => $this->duration($totalSeconds),
            'total_hours' => $this->hours($totalSeconds),
            'total_amount' => $this->money($totalSeconds, $rate, $currency),
            'groups' => array_map(fn (array $group) => [
                'title' => $group['title'],
                'duration' => $this->duration($group['seconds']),
                'hours' => $this->hours($group['seconds']),
                'amount' => $this->money($group['seconds'], $rate, $currency),
                'logs' => $group['logs']->map(fn (TimeLog $log) => [
                    'date' => $log->started_at->setTimezone($timezone)->format('Y-m-d'),
                    'started' => $log->started_at->setTimezone($timezone)->format('H:i'),
                    'duration' => $this->duration((int) $log->duration_seconds),
                    'hours' => $this->hours((int) $log->duration_seconds),
                    'note' => $log->note,
                ])->all(),
            ], $groups),
        ];
    }

    private function filename(string $subject, CarbonImmutable $from, CarbonImmutable $to): string
    {
        return sprintf(
            'timesheet-%s-%s-%s.pdf',
            Str::slug($subject),
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
        );
    }

    private function duration(int $seconds): string
    {
        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60,
        );
    }

    private function hours(int $seconds): string
    {
        return Number::format($seconds / 3600, precision: 2);
    }

    private function money(int $seconds, ?float $rate, string $currency): ?string
    {
        if ($rate === null) {
            return null;
        }

        return Number::currency(round($seconds / 3600 * $rate, 2), $currency);
    }
}
