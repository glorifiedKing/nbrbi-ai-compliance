<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BuildingClass;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessDrawingJob;
use App\Models\Block;
use App\Models\Drawing;
use App\Models\DrawingVersion;
use App\Models\Project;
use App\Models\User;
use App\Services\FeeCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * List all projects and display the project creation form.
     */
    public function index(Request $request): View
    {
        $query = Project::with(['owner:id,name,email,bims_id', 'blocks.drawings.currentVersion.complianceResult'])
            ->withCount(['blocks', 'drawings'])
            ->latest();

        if ($request->filled('occupancy_class')) {
            $query->where('occupancy_class', $request->query('occupancy_class'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $projects = $query->paginate(12);

        return view('admin.projects.index', compact('projects'));
    }

    /**
     * Create a new project with a default Main Block.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100', 'unique:projects,reference_number'],
            'occupancy_class' => ['nullable', 'string', 'max:100'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $userId = $request->user()?->id ?? User::first()?->id;
        $referenceNumber = ! empty($validated['reference_number'])
            ? $validated['reference_number']
            : ('NBRB-'.strtoupper(Str::random(4)).'-'.date('Y').'-'.rand(100, 999));

        $project = Project::create([
            'owner_id' => $userId,
            'name' => $validated['name'],
            'reference_number' => $referenceNumber,
            'occupancy_class' => $validated['occupancy_class'] ?? BuildingClass::CLASS_B->value,
            'share_token' => Str::random(32),
            'is_public' => (bool) ($validated['is_public'] ?? false),
            'status' => 'draft',
        ]);

        $project->blocks()->create([
            'name' => 'Main Block',
            'description' => 'Primary structure',
            'floors' => [
                [
                    'floor_number' => 0,
                    'floor_name' => 'Ground Floor',
                    'name' => 'Ground Floor',
                    'gross_floor_area_sqm' => 0.0,
                ],
            ],
        ]);

        return redirect()->route('admin.projects.show', $project)
            ->with('success', "Project \"{$project->name}\" ({$project->reference_number}) created with default Main Block.");
    }

    /**
     * Display the project workspace for managing blocks and uploading drawings.
     */
    public function show(Project $project): View
    {
        $project->load([
            'owner:id,name,email,bims_id',
            'blocks.drawings.currentVersion.complianceResult',
            'blocks.drawings.versions' => function ($query): void {
                $query->with(['complianceResult', 'uploader:id,name,email'])
                    ->orderByDesc('version_number')
                    ->limit(5);
            },
        ]);

        $hasActiveJobs = false;
        foreach ($project->blocks as $block) {
            foreach ($block->drawings as $drawing) {
                foreach ($drawing->versions as $v) {
                    if (in_array($v->status, ['pending', 'analyzing'], true)) {
                        $hasActiveJobs = true;
                        break 3;
                    }
                }
            }
        }

        return view('admin.projects.show', compact('project', 'hasActiveJobs'));
    }

    /**
     * Update project metadata.
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'occupancy_class' => ['required', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:draft,in_progress,compliant,non_compliant'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $project->update([
            'name' => $validated['name'],
            'occupancy_class' => $validated['occupancy_class'],
            'status' => $validated['status'] ?? $project->status,
            'is_public' => (bool) ($validated['is_public'] ?? false),
        ]);

        FeeCalculationService::recalculateProjectTotals($project);

        return redirect()->route('admin.projects.show', $project)
            ->with('success', 'Project details updated successfully.');
    }

    /**
     * Delete a project and its associated blocks and drawings.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $name = $project->name;
        $project->delete();

        return redirect()->route('admin.projects.index')
            ->with('success', "Project \"{$name}\" deleted successfully.");
    }

    /**
     * Add a new block to the project.
     */
    public function storeBlock(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'floors' => ['nullable', 'array'],
            'floors.*.floor_number' => ['nullable', 'integer'],
            'floors.*.floor_name' => ['nullable', 'string', 'max:100'],
            'floors.*.gross_floor_area_sqm' => ['nullable', 'numeric', 'min:0'],
        ]);

        $floors = $this->normalizeFloorsInput($validated['floors'] ?? []);
        if (empty($floors)) {
            $floors = [
                [
                    'floor_number' => 0,
                    'floor_name' => 'Ground Floor',
                    'name' => 'Ground Floor',
                    'gross_floor_area_sqm' => 0.0,
                ],
            ];
        }

        $block = $project->blocks()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'floors' => $floors,
        ]);

        FeeCalculationService::recalculateProjectTotals($project);

        return redirect()->route('admin.projects.show', $project)
            ->with('success', "Block \"{$block->name}\" added to project.");
    }

    /**
     * Update an existing block's name, description, and floor details JSONB.
     */
    public function updateBlock(Request $request, Project $project, Block $block): RedirectResponse
    {
        abort_unless($block->project_id === $project->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'floors' => ['nullable', 'array'],
            'floors.*.floor_number' => ['nullable', 'integer'],
            'floors.*.floor_name' => ['nullable', 'string', 'max:100'],
            'floors.*.gross_floor_area_sqm' => ['nullable', 'numeric', 'min:0'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ];

        if ($request->has('floors')) {
            $updateData['floors'] = $this->normalizeFloorsInput($validated['floors'] ?? []);
        }

        $block->update($updateData);

        FeeCalculationService::recalculateProjectTotals($project);

        return redirect()->route('admin.projects.show', $project)
            ->with('success', "Block \"{$block->name}\" updated successfully.");
    }

    /**
     * Delete a block from the project.
     */
    public function destroyBlock(Project $project, Block $block): RedirectResponse
    {
        abort_unless($block->project_id === $project->id, 404);

        $blockName = $block->name;
        $block->delete();

        FeeCalculationService::recalculateProjectTotals($project);

        return redirect()->route('admin.projects.show', $project)
            ->with('success', "Block \"{$blockName}\" removed from project.");
    }

    /**
     * Upload a drawing PDF for a specific block and discipline, and dispatch AI compliance processing.
     */
    public function uploadDrawing(Request $request, Project $project, Block $block): RedirectResponse
    {
        abort_unless($block->project_id === $project->id, 404);

        $validated = $request->validate([
            'discipline' => ['required', 'string', 'in:architectural,structural,mechanical,electrical'],
            'drawing_file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ]);

        $file = $request->file('drawing_file');
        $fileHash = hash_file('sha256', $file->getRealPath());
        $fileSize = $file->getSize();
        $originalName = $file->getClientOriginalName();
        $userId = $request->user()?->id ?? User::first()?->id;

        $drawingVersion = DB::transaction(function () use ($block, $validated, $file, $fileHash, $fileSize, $originalName, $userId) {
            $drawing = Drawing::firstOrCreate([
                'block_id' => $block->id,
                'discipline' => strtolower($validated['discipline']),
            ]);

            $latestVersion = DrawingVersion::where('drawing_id', $drawing->id)
                ->orderByDesc('version_number')
                ->first();

            $nextVersionNumber = $latestVersion ? ($latestVersion->version_number + 1) : 1;

            if ($latestVersion && $latestVersion->file_hash === $fileHash && $latestVersion->status === 'completed') {
                return $latestVersion;
            }

            $storedPath = $file->storeAs(
                "drawings/{$drawing->id}",
                "v{$nextVersionNumber}_".time()."_{$originalName}",
                'local'
            );

            return DrawingVersion::create([
                'drawing_id' => $drawing->id,
                'uploaded_by' => $userId,
                'version_number' => $nextVersionNumber,
                'file_name' => $storedPath,
                'file_size_bytes' => $fileSize,
                'file_hash' => $fileHash,
                'status' => 'pending',
            ]);
        });

        if ($drawingVersion->status === 'pending') {
            ProcessDrawingJob::dispatch($drawingVersion);
        }

        if ($project->status === 'draft') {
            $project->update(['status' => 'in_progress']);
        }

        return redirect()->route('admin.projects.show', $project)
            ->with('success', ucfirst($validated['discipline'])." drawing \"{$originalName}\" (v{$drawingVersion->version_number}) uploaded for {$block->name} and queued for AI compliance audit.");
    }

    /**
     * Display the consolidated compliance audit report for the project across all blocks and disciplines.
     */
    public function report(Project $project): View
    {
        $project->load([
            'owner:id,name,email,bims_id',
            'blocks.drawings.currentVersion.complianceResult',
            'blocks.drawings.versions.complianceResult',
        ]);

        $totalDrawings = 0;
        $processedDrawings = 0;
        $totalPass = 0;
        $totalWarning = 0;
        $totalFail = 0;
        $allDiscrepancies = [];
        $hasActiveJobs = false;

        $disciplineSummaries = [
            'architectural' => ['total' => 0, 'passed' => 0, 'failed' => 0, 'warnings' => 0, 'status' => 'NOT_SUBMITTED'],
            'structural' => ['total' => 0, 'passed' => 0, 'failed' => 0, 'warnings' => 0, 'status' => 'NOT_SUBMITTED'],
            'mechanical' => ['total' => 0, 'passed' => 0, 'failed' => 0, 'warnings' => 0, 'status' => 'NOT_SUBMITTED'],
            'electrical' => ['total' => 0, 'passed' => 0, 'failed' => 0, 'warnings' => 0, 'status' => 'NOT_SUBMITTED'],
        ];

        foreach ($project->blocks as $block) {
            foreach ($block->drawings as $drawing) {
                $totalDrawings++;
                $discipline = strtolower($drawing->discipline);
                if (isset($disciplineSummaries[$discipline])) {
                    $disciplineSummaries[$discipline]['total']++;
                }

                foreach ($drawing->versions as $version) {
                    if (in_array($version->status, ['pending', 'analyzing'], true)) {
                        $hasActiveJobs = true;
                    }
                }

                $currentVersion = $drawing->currentVersion ?? $drawing->versions->first();
                if ($currentVersion && $currentVersion->complianceResult) {
                    $processedDrawings++;
                    $result = $currentVersion->complianceResult;
                    $status = $result->overall_status;

                    if ($status === 'PASS') {
                        $totalPass++;
                        if (isset($disciplineSummaries[$discipline])) {
                            $disciplineSummaries[$discipline]['passed']++;
                        }
                    } elseif ($status === 'WARNING') {
                        $totalWarning++;
                        if (isset($disciplineSummaries[$discipline])) {
                            $disciplineSummaries[$discipline]['warnings']++;
                        }
                    } else {
                        $totalFail++;
                        if (isset($disciplineSummaries[$discipline])) {
                            $disciplineSummaries[$discipline]['failed']++;
                        }
                    }

                    $discrepancies = is_array($result->discrepancies) ? $result->discrepancies : [];
                    foreach ($discrepancies as $d) {
                        if (! is_array($d)) {
                            continue;
                        }
                        $allDiscrepancies[] = array_merge($d, [
                            'block' => $block->name,
                            'floor' => $d['floor'] ?? $d['floor_name'] ?? null,
                            'discipline' => $drawing->discipline,
                            'version' => "v{$currentVersion->version_number}",
                        ]);
                    }
                }
            }
        }

        foreach ($disciplineSummaries as $disc => &$data) {
            if ($data['total'] === 0) {
                $data['status'] = 'NOT_SUBMITTED';
            } elseif ($data['failed'] > 0) {
                $data['status'] = 'NON_COMPLIANT';
            } elseif (($data['passed'] + $data['warnings']) === $data['total']) {
                $data['status'] = 'COMPLIANT';
            } else {
                $data['status'] = 'IN_REVIEW';
            }
        }
        unset($data);

        $overallProjectStatus = match (true) {
            $totalDrawings === 0 => 'NO_SUBMISSIONS',
            $totalFail > 0 => 'NON_COMPLIANT',
            $processedDrawings === $totalDrawings && $totalFail === 0 => 'COMPLIANT',
            default => 'IN_PROGRESS',
        };

        $feeRate = (float) ($project->fee_rate_per_sqm_ugx ?? FeeCalculationService::getRatePerSqm($project->occupancy_class));

        return view('admin.projects.report', compact(
            'project',
            'totalDrawings',
            'processedDrawings',
            'totalPass',
            'totalWarning',
            'totalFail',
            'allDiscrepancies',
            'disciplineSummaries',
            'overallProjectStatus',
            'feeRate',
            'hasActiveJobs'
        ));
    }

    /**
     * Normalize floor input rows into structured JSONB items.
     *
     * @param  array<int, mixed>  $rawFloors
     * @return list<array<string, mixed>>
     */
    protected function normalizeFloorsInput(array $rawFloors): array
    {
        $floors = [];

        foreach (array_values($rawFloors) as $index => $floor) {
            if (! is_array($floor)) {
                continue;
            }

            $floorName = trim((string) ($floor['floor_name'] ?? $floor['name'] ?? ''));
            $hasNumber = isset($floor['floor_number']) && $floor['floor_number'] !== '';
            $hasArea = isset($floor['gross_floor_area_sqm']) && $floor['gross_floor_area_sqm'] !== '';

            if ($floorName === '' && ! $hasNumber && ! $hasArea) {
                continue;
            }

            $floorNumber = $hasNumber ? (int) $floor['floor_number'] : $index;
            if ($floorName === '') {
                $floorName = $floorNumber === 0 ? 'Ground Floor' : "Floor {$floorNumber}";
            }

            $floors[] = array_merge($floor, [
                'floor_number' => $floorNumber,
                'floor_name' => $floorName,
                'name' => $floorName,
                'gross_floor_area_sqm' => $hasArea ? (float) $floor['gross_floor_area_sqm'] : 0.0,
            ]);
        }

        usort($floors, fn (array $a, array $b): int => ((int) $a['floor_number']) <=> ((int) $b['floor_number']));

        return array_values($floors);
    }
}
