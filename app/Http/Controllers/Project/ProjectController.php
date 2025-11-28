<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    private const STATUS_OPTIONS = [
        'planned' => 'Perencanaan',
        'ongoing' => 'Berjalan',
        'done' => 'Selesai',
        'archived' => 'Arsip',
    ];

    public function index(Request $request)
    {
        $projectsQuery = Project::query();

        if ($search = trim((string) $request->input('search'))) {
            $projectsQuery->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('client', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $projectsQuery->where('status', $status);
        }

        $perPage = $request->integer('per_page', 10) ?: 10;
        $perPage = max(min($perPage, 100), 1);

        $projects = $projectsQuery
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('master.projects.index', [
            'projects' => $projects,
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Master Proyek',
            'user' => Auth::user(),
        ]);
    }

    public function create()
    {
        return view('master.projects.create', [
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Tambah Proyek',
            'user' => Auth::user(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateProject($request);

        $project = Project::create($validated);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Proyek berhasil ditambahkan.');
    }

    public function show(Project $project)
    {
        return view('master.projects.show', [
            'project' => $project,
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Detail Proyek',
            'user' => Auth::user(),
        ]);
    }

    public function edit(Project $project)
    {
        return view('master.projects.edit', [
            'project' => $project,
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Ubah Proyek',
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $validated = $this->validateProject($request, $project);

        $project->update($validated);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Proyek berhasil diperbarui.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Proyek berhasil dihapus.');
    }

    protected function validateProject(Request $request, ?Project $project = null): array
    {
        $statuses = array_keys(self::STATUS_OPTIONS);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'client' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in($statuses)],
        ]);
    }
}
