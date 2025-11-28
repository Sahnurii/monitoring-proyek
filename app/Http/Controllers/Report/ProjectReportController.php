<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ProjectReportController extends Controller
{
    private const STATUS_OPTIONS = [
        'planned' => 'Perencanaan',
        'ongoing' => 'Berjalan',
        'done' => 'Selesai',
        'archived' => 'Arsip',
    ];

    public function index(Request $request)
    {
        Carbon::setLocale(config('app.locale', 'id'));

        $filters = $this->resolveFilters($request);
        $reportData = $this->collectReportData($filters);

        return view('report.projects.index', [
            'title' => 'Laporan Data Proyek',
            'user' => Auth::user(),
            'filters' => $filters,
            'filterQuery' => $this->formatFilterQuery($filters),
        ] + $reportData);
    }

    public function exportPdf(Request $request)
    {
        Carbon::setLocale(config('app.locale', 'id'));

        $filters = $this->resolveFilters($request);
        $reportData = $this->collectReportData($filters);

        $data = $reportData + [
            'title' => 'Laporan Data Proyek',
            'generatedAt' => Carbon::now(),
            'filters' => $filters,
            'filterQuery' => $this->formatFilterQuery($filters),
        ];

        $pdf = Pdf::loadView('report.projects.pdf', $data)
            ->setPaper('a4', 'landscape');

        $fileName = 'laporan-proyek-' . Carbon::now()->format('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }

    private function resolveFilters(Request $request): array
    {
        $status = trim((string) $request->input('status'));
        $status = array_key_exists($status, self::STATUS_OPTIONS) ? $status : null;

        $client = trim((string) $request->input('client'));
        $client = $client !== '' ? $client : null;

        $startDate = $this->parseDate($request->input('start_date'));
        $endDate = $this->parseDate($request->input('end_date'));

        if ($startDate && $endDate && $endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'status' => $status,
            'client' => $client,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectReportData(array $filters): array
    {
        $projects = $this->queryProjects($filters);

        return [
            'statuses' => self::STATUS_OPTIONS,
            'projects' => $projects,
            'summary' => $this->buildSummary($projects),
            'statusSummary' => $this->buildStatusSummary($projects),
            'timeline' => $this->buildTimeline($projects),
            'topClients' => $this->buildTopClients($projects),
        ];
    }

    /**
     * @param  array{status: ?string, client: ?string, start_date: ?Carbon, end_date: ?Carbon}  $filters
     * @return Collection<int, Project>
     */
    private function queryProjects(array $filters): Collection
    {
        $query = Project::query();

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['client'] !== null) {
            $query->where('client', 'like', '%' . $filters['client'] . '%');
        }

        if ($filters['start_date']) {
            $query->whereDate('start_date', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $query->whereDate('end_date', '<=', $filters['end_date']);
        }

        return $query
            ->orderByRaw('COALESCE(start_date, end_date) is null')
            ->orderByRaw('COALESCE(start_date, end_date) asc')
            ->orderBy('name')
            ->get();
    }

    private function buildSummary(Collection $projects): array
    {
        $totalProjects = $projects->count();
        $totalBudget = (float) $projects->sum('budget');

        $completed = $projects->where('status', 'done')->count();
        $active = $projects->where('status', 'ongoing')->count();
        $planned = $projects->where('status', 'planned')->count();
        $archived = $projects->where('status', 'archived')->count();

        $overdue = $projects->filter(function (Project $project) {
            return $this->isOverdue($project);
        })->count();

        $durationValues = $projects
            ->filter(fn (Project $project) => $project->start_date && $project->end_date)
            ->map(fn (Project $project) => $project->start_date->diffInDays($project->end_date) + 1);

        $averageDuration = $durationValues->isNotEmpty()
            ? round($durationValues->avg())
            : null;

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $active,
            'planned_projects' => $planned,
            'completed_projects' => $completed,
            'archived_projects' => $archived,
            'overdue_projects' => $overdue,
            'total_budget' => $totalBudget,
            'average_budget' => $totalProjects > 0 ? $totalBudget / $totalProjects : 0.0,
            'completion_rate' => $totalProjects > 0 ? round(($completed / $totalProjects) * 100, 1) : 0.0,
            'average_duration' => $averageDuration,
        ];
    }

    private function buildStatusSummary(Collection $projects): array
    {
        $statusCollection = collect(self::STATUS_OPTIONS)->map(function (string $label, string $key) use ($projects) {
            $items = $projects->where('status', $key);

            return [
                'key' => $key,
                'label' => $label,
                'count' => $items->count(),
                'budget' => (float) $items->sum('budget'),
            ];
        })->values();

        $others = $projects
            ->reject(fn (Project $project) => array_key_exists($project->status, self::STATUS_OPTIONS))
            ->groupBy('status')
            ->map(function (Collection $items, string $status) {
                return [
                    'key' => $status,
                    'label' => ucfirst($status ?: 'Lainnya'),
                    'count' => $items->count(),
                    'budget' => (float) $items->sum('budget'),
                ];
            })
            ->values();

        return $statusCollection->merge($others)->toArray();
    }

    private function buildTimeline(Collection $projects): array
    {
        $grouped = $projects
            ->filter(fn (Project $project) => (bool) $project->start_date)
            ->groupBy(fn (Project $project) => $project->start_date->format('Y-m'))
            ->sortKeys();

        return $grouped->map(function (Collection $items, string $period) {
            $first = $items->first();
            $start = $first->start_date instanceof Carbon
                ? $first->start_date->copy()
                : Carbon::parse($first->start_date);

            return [
                'period' => $period,
                'label' => $start->translatedFormat('F Y'),
                'count' => $items->count(),
                'budget' => (float) $items->sum('budget'),
            ];
        })->values()->all();
    }

    private function buildTopClients(Collection $projects): array
    {
        return $projects
            ->filter(fn (Project $project) => $project->client !== null && trim((string) $project->client) !== '')
            ->groupBy(fn (Project $project) => trim((string) $project->client))
            ->map(function (Collection $items, string $client) {
                return [
                    'client' => $client,
                    'projects' => $items->count(),
                    'total_budget' => (float) $items->sum('budget'),
                    'latest_end' => $items
                        ->filter(fn (Project $project) => (bool) $project->end_date)
                        ->max(fn (Project $project) => $project->end_date?->getTimestamp()),
                ];
            })
            ->values()
            ->sortByDesc(fn (array $row) => [$row['projects'], $row['total_budget']])
            ->take(5)
            ->map(function (array $row) {
                if ($row['latest_end']) {
                    $row['latest_end'] = Carbon::createFromTimestamp($row['latest_end'])->translatedFormat('d M Y');
                } else {
                    $row['latest_end'] = null;
                }

                return $row;
            })
            ->values()
            ->all();
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function isOverdue(Project $project): bool
    {
        if (! $project->end_date) {
            return false;
        }

        if (in_array($project->status, ['done', 'archived'], true)) {
            return false;
        }

        return $project->end_date->isPast();
    }

    /**
     * @param  array{status: ?string, client: ?string, start_date: ?Carbon, end_date: ?Carbon}  $filters
     * @return array<string, string>
     */
    private function formatFilterQuery(array $filters): array
    {
        return array_filter([
            'status' => $filters['status'],
            'client' => $filters['client'],
            'start_date' => $filters['start_date']?->toDateString(),
            'end_date' => $filters['end_date']?->toDateString(),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
