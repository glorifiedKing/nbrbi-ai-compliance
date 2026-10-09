<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    /**
     * List all projects associated with the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $projects = Project::where('owner_id', $userId)
            ->orWhereHas('collaborators', function ($query) use ($userId): void {
                $query->where('user_id', $userId);
            })
            ->with(['owner:id,name,email,bims_id', 'blocks.floors.drawings.currentVersion.complianceResult'])
            ->withCount(['blocks'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $projects,
        ]);
    }

    /**
     * Create a new project.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100', 'unique:projects,reference_number'],
            'occupancy_class' => ['nullable', 'string', 'max:100'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $referenceNumber = $validated['reference_number'] ?? ('NBRB-'.strtoupper(Str::random(4)).'-'.date('Y').'-'.rand(100, 999));
        $shareToken = Str::random(32);

        $project = Project::create([
            'owner_id' => $request->user()->id,
            'name' => $validated['name'],
            'reference_number' => $referenceNumber,
            'occupancy_class' => $validated['occupancy_class'] ?? 'Class A (Residential)',
            'share_token' => $shareToken,
            'is_public' => $validated['is_public'] ?? false,
            'status' => 'draft',
        ]);

        // Automatically create a default "Main Block" to simplify mobile onboarding
        $block = $project->blocks()->create([
            'name' => 'Main Block',
            'description' => 'Primary structure',
        ]);

        // Create default Ground Floor
        $block->floors()->create([
            'floor_number' => 0,
            'name' => 'Ground Floor',
        ]);

        $project->load(['blocks.floors', 'owner:id,name,email,bims_id']);

        return response()->json([
            'status' => 'success',
            'message' => 'Project created successfully',
            'data' => $project,
        ], 201);
    }

    /**
     * View detailed project structure including blocks, floors, and compliance status.
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorizeAccess($request->user(), $project);

        $project->load([
            'owner:id,name,email,bims_id',
            'collaborators:id,name,email,bims_id',
            'blocks.floors.drawings.currentVersion.complianceResult',
            'blocks.floors.drawings.versions' => function ($query): void {
                $query->orderByDesc('version_number')->limit(5);
            },
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $project,
        ]);
    }

    /**
     * Update project metadata.
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorizeOwner($request->user(), $project);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'occupancy_class' => ['sometimes', 'string', 'max:100'],
            'is_public' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', 'in:draft,in_progress,compliant,non_compliant'],
        ]);

        $project->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Project updated successfully',
            'data' => $project,
        ]);
    }

    /**
     * Join a project using its anti-duplication share token.
     */
    public function joinByToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'share_token' => ['required', 'string', 'max:64'],
            'role' => ['nullable', 'string', 'in:lead,architect,structural_eng,mep_eng,reviewer,viewer'],
        ]);

        $project = Project::where('share_token', $validated['share_token'])->first();

        if (! $project) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired project share token.',
            ], 404);
        }

        $userId = $request->user()->id;

        if ($project->owner_id === $userId) {
            return response()->json([
                'status' => 'success',
                'message' => 'You are the owner of this project.',
                'data' => $project,
            ]);
        }

        // Attach user if not already attached
        $existing = ProjectUser::where('project_id', $project->id)
            ->where('user_id', $userId)
            ->first();

        if (! $existing) {
            ProjectUser::create([
                'project_id' => $project->id,
                'user_id' => $userId,
                'role' => $validated['role'] ?? 'viewer',
                'status' => 'accepted',
            ]);
        }

        $project->load(['owner:id,name,email', 'blocks.floors']);

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully joined project.',
            'data' => $project,
        ]);
    }

    /**
     * Add a collaborator to the project by email.
     */
    public function addCollaborator(Request $request, Project $project): JsonResponse
    {
        $this->authorizeOwner($request->user(), $project);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'string', 'in:lead,architect,structural_eng,mep_eng,reviewer,viewer'],
        ]);

        $collaborator = User::where('email', $validated['email'])->first();

        if (! $collaborator) {
            return response()->json([
                'status' => 'error',
                'message' => 'User with this email not registered on BIMS Uganda.',
            ], 404);
        }

        ProjectUser::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $collaborator->id],
            ['role' => $validated['role'], 'status' => 'accepted']
        );

        return response()->json([
            'status' => 'success',
            'message' => "Collaborator [{$collaborator->name}] added as [{$validated['role']}].",
        ]);
    }

    protected function authorizeAccess(User $user, Project $project): void
    {
        if ($project->is_public || $project->owner_id === $user->id) {
            return;
        }

        $isCollaborator = $project->collaborators()->where('user_id', $user->id)->exists();

        if (! $isCollaborator) {
            abort(403, 'Unauthorized access to this project.');
        }
    }

    protected function authorizeOwner(User $user, Project $project): void
    {
        if ($project->owner_id !== $user->id) {
            abort(403, 'Only the project owner can perform this operation.');
        }
    }
}
